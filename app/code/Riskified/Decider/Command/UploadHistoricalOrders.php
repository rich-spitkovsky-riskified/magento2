<?php
namespace Riskified\Decider\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Riskified\Common\Riskified;
use Riskified\Common\Validations;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport\CurlTransport;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;

class UploadHistoricalOrders extends Command
{
    protected $_scopeConfig;
    protected $_orderRepository;
    protected $_searchCriteriaBuilder;
    protected $_orderHelper;
    protected $_transport;
    protected $_totalUploaded = 0;
    protected $_currentPage = 1;
    protected $_orders;

    private $startDateFilter = null;
    private $endDateFilter = null;
    private $filterBuilder;

    const BATCH_SIZE = 10;
    const START_DATE = 'startdate';
    const END_DATE = 'enddate';

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $state->setAreaCode('adminhtml');

        $this->_scopeConfig             = $scopeConfig;
        $this->_orderRepository         = $orderRepository;
        $this->_searchCriteriaBuilder   = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_orderHelper = $objectManager->get('\Riskified\Decider\Api\Order\Helper');

        $this->_transport = new CurlTransport(new Signature\HttpDataSignature());
        $this->_transport->timeout = 15;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::START_DATE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Start Date'
            ),
            new InputOption(
                self::END_DATE,
                null,
                InputOption::VALUE_OPTIONAL,
                'END Date'
            ),
        ];

        $this->setName('riskified:sync:historical-orders');
        $this->setDescription('Send your orders to Riskified.');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $authToken = $this->_scopeConfig->getValue('riskified/riskified/key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $env = constant('\Riskified\Common\Env::' . $this->_scopeConfig->getValue('riskified/riskified/env'));
        $domain = $this->_scopeConfig->getValue('riskified/riskified/domain');

        $output->writeln(sprintf("Riskified auth token: %s", $authToken));
        $output->writeln(sprintf("Riskified shop domain: %s", $domain));
        $output->writeln(sprintf("Riskified target environment: %s", $env));
        $output->writeln("***********");

        Riskified::init($domain, $authToken, $env, Validations::SKIP);

        $this->startDateFilter = $input->getOption("startdate");
        $this->endDateFilter = $input->getOption("enddate");

        $fullOrderRepository = $this->getEntireCollection();
        $totalCount = $fullOrderRepository->getSize();

        $output->writeln(sprintf("Starting to upload orders, total_count: %s \n", $totalCount));

        $this->getCollection();

        while ($this->_totalUploaded < $totalCount) {
            try {
                $this->postOrders();
                $this->_totalUploaded += count($this->_orders);
                $this->_currentPage++;

                $output->writeln(sprintf("Uploaded %s of %s orders\n", $this->_totalUploaded, $totalCount));

                $this->getCollection();
            } catch (\Exception $e) {
                $output->writeln("<error>".$e->getMessage()."</error> \n");
                exit(1);
            }
        }
    }

    /**
     * Retrieve prepared order collection for counting values
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function getEntireCollection()
    {
        if ($this->startDateFilter) {
            try {
                $startDate = new \DateTime($this->startDateFilter);
                $filterStartDate = $this->filterBuilder
                    ->setField("main_table.created_at")
                    ->setValue($startDate->format("Y-m-d 00:00:00"))
                    ->setConditionType("gteq")
                    ->create();
                $this->_searchCriteriaBuilder->addFilter($filterStartDate);
            } catch(\Exception $e) {}
        }
        if ($this->endDateFilter) {
            try {
                $startDate = new \DateTime($this->endDateFilter);
                $filterStartDate = $this->filterBuilder
                    ->setField("main_table.created_at")
                    ->setValue($startDate->format("Y-m-d 23:59:59"))
                    ->setConditionType("lteq")
                    ->create();
                $this->_searchCriteriaBuilder->addFilter($filterStartDate);
            } catch(\Exception $e) {}
        }

        $searchCriteria = $this->_searchCriteriaBuilder->create();

        $orderResult = $this
            ->_orderRepository
            ->getList($searchCriteria);

        return $orderResult;
    }

    /**
     * Retrieve paginated collection
     *
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    protected function getCollection()
    {
        $this->_searchCriteriaBuilder
            ->setPageSize(self::BATCH_SIZE)
            ->setCurrentPage($this->_currentPage);

        $searchCriteria = $this->_searchCriteriaBuilder->create();

        $orderResult = $this->_orderRepository->getList($searchCriteria);

        $this->_orders = $orderResult->getItems();

        return $this->_orders;
    }

    /**
     * Sends orders to endpoint
     *
     * @return void
     */
    protected function postOrders() {
        if (!$this->_scopeConfig->getValue('riskified/riskified_general/enabled')) {
            return;
        }
        $orders = array();

        foreach ($this->_orders as $model) {
            $orders[] = $this->prepareOrder($model);
        }
        $this->_transport->sendHistoricalOrders($orders);
    }

    /**
     * @param Model\Order $model
     *
     * @return Model\Order
     */
    protected function prepareOrder($model) {
        $gateway = 'unavailable';
        if ($model->getPayment()) {
            $gateway = $model->getPayment()->getMethod();
        }

        $this->_orderHelper->setOrder($model);

        $order_array = array(
            'id' => $this->_orderHelper->getOrderOrigId(),
            'name' => $model->getIncrementId(),
            'email' => $model->getCustomerEmail(),
            'created_at' => $this->_orderHelper->formatDateAsIso8601($model->getCreatedAt()),
            'currency' => $model->getOrderCurrencyCode(),
            'updated_at' => $this->_orderHelper->formatDateAsIso8601($model->getUpdatedAt()),
            'gateway' => $gateway,
            'browser_ip' => $this->_orderHelper->getRemoteIp(),
            'note' => $model->getCustomerNote(),
            'total_price' => $model->getGrandTotal(),
            'total_discounts' => $model->getDiscountAmount(),
            'subtotal_price' => $model->getBaseSubtotalInclTax(),
            'discount_codes' => $this->_orderHelper->getDiscountCodes($model),
            'taxes_included' => true,
            'total_tax' => $model->getBaseTaxAmount(),
            'total_weight' => $model->getWeight(),
            'cancelled_at' => $this->_orderHelper->formatDateAsIso8601($this->_orderHelper->getCancelledAt()),
            'financial_status' => $model->getState(),
            'fulfillment_status' => $model->getStatus(),
            'vendor_id' => $model->getStoreId(),
            'vendor_name' => $model->getStoreName(),
        );

        $order = new Model\Order(array_filter($order_array, 'strlen'));
        $order->customer = $this->_orderHelper->getCustomer();
        $order->shipping_address = $this->_orderHelper->getShippingAddress();
        $order->billing_address = $this->_orderHelper->getBillingAddress();
        $order->payment_details = $this->_orderHelper->getPaymentDetails();
        $order->line_items = $this->_orderHelper->getLineItems();
        $order->shipping_lines = $this->_orderHelper->getShippingLines();

        return $order;
    }
}