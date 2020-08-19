<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Kento\CustomInvoicePrint\Model\Order\Pdf\Items\Invoice;

/**
 * Sales Order Invoice Pdf default items renderer
 */
class DefaultInvoice extends \Magento\Sales\Model\Order\Pdf\Items\AbstractItems
{
    /**
     * Core string
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

        /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    private $orderItemRepository;



    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;



    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,

        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,    
        
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,

        array $data = []
    ) {
        $this->string = $string;
        $this->orderItemRepository = $orderItemRepository;
       $this->searchCriteriaBuilder = $searchCriteriaBuilder;

       $this->filterGroupBuilder = $filterGroupBuilder;
       $this->filterBuilder = $filterBuilder;

        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Draw item line
     *
     * @return void
     */
    public function draw()
    {
        
        $order = $this->getOrder();
        $item = $this->getItem();
        $productId = $item->getData('product_id');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
        $productLocation = $product->getLocation();
        $attr = $product->getResource()->getAttribute('location');
        if ($attr->usesSource()) {
           $optionText = $attr->getSource()->getOptionText($productLocation);
        }
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];

        $sku = $item->getSku();
        $itemId = $item->getData('order_item_id');


        // draw Product name
        if($sku != '106891')
        $lines[0] = [['text' => $this->string->split($item->getName(), 35, true, true), 'feed' => 35]];

        else{
            //die(print_r($productOptions,true));
            $this->searchCriteriaBuilder->addFilter('item_id', $itemId , 'eq');
            $collection = $this->orderItemRepository->getList(
                $this->searchCriteriaBuilder->create()
            );
            $productOptions=($collection->getData()[0]['product_options']);
            $decodedResult=json_decode($productOptions);
           // die(serialize($decodedResult->info_buyRequest->options));
            // $lines[0] = [['text' => serialize($productOptions),'feed' => 130]];
            $lines[0] = [['text' => $this->string->split($item->getName(), 35, true, true), 'feed' => 35]];

           foreach ($decodedResult->info_buyRequest->options as $option => $optionValue) {
                
            switch ($option) {          
            case "28" : $key="Hear Us"; break;  // hear about us
            case "29" : $key="Backing Dimension"; break;  // backing dimension
            case "30" : $key="Placement"; break;  // Placement Direction
            case "31" : $key="Sew Yeah Choose Pattern"; break;  // Sewyeah Choose Pattern
            case "32" : $key="Trim Backing"; break;  // Trim Off Excess Backing
            case "33" : $key="Thread Color"; break;  // Thread Color
            case "34" : $key="Bating"; break;  // Batting
            case "35" : $key="Additional Note"; break;  // Additional Notes
            case "36" : $key="Pattern Theme"; break;  // pattern themes
            case "37" : $key="Initials"; break;
            default: $key=$option; break;
            }


              //  draw options label
                $lines[][] = [
                    'text' => $this->string->split($this->filterManager->stripTags($key)." : " .$this->filterManager->stripTags($optionValue), 35, true, true),
                    'font' => 'italic',
                    'feed' => 45,
                ];

           }
        }
        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->getSku($item), 17),
            'feed' => 225,
            'align' => 'right',
        ];
        
        // draw Location
        $lines[0][] = [
            'text' => $optionText,
            'feed' => 290,
            'align' => 'right',
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 435, 'align' => 'right'];

        // draw item Prices
        $i = 0;
        $prices = $this->getItemPricesForDisplay();
        $feedPrice = 375;
        $feedSubtotal = $feedPrice + 190;
        foreach ($prices as $priceData) {
            if (isset($priceData['label'])) {
                // draw Price label
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedPrice, 'align' => 'right'];
                // draw Subtotal label
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedSubtotal, 'align' => 'right'];
                $i++;
            }
            // draw Price
            $lines[$i][] = [
                'text' => $priceData['price'],
                'feed' => $feedPrice,
                'font' => 'bold',
                'align' => 'right',
            ];
            // draw Subtotal
            $lines[$i][] = [
                'text' => $priceData['subtotal'],
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
            ];
            $i++;
        }

        // draw Tax
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getTaxAmount()),
            'feed' => 495,
            'font' => 'bold',
            'align' => 'right',
        ];

        // custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = [
                    'text' => $this->string->split($this->filterManager->stripTags($option['label']), 40, true, true),
                    'font' => 'italic',
                    'feed' => 35,
                ];

                if ($option['value']) {
                    if (isset($option['print_value'])) {
                        $printValue = $option['print_value'];
                    } else {
                        $printValue = $this->filterManager->stripTags($option['value']);
                    }
                    $values = explode(', ', $printValue);
                    foreach ($values as $value) {
                        $lines[][] = ['text' => $this->string->split($value, 30, true, true), 'feed' => 40];
                    }
                }
            }
        }

        $lineBlock = ['lines' => $lines, 'height' => 20];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }
}
