<?php
class IWE_EmailReminder_Block_Adminhtml_Rule_Grid extends AW_Followupemail_Block_Adminhtml_Rule_Grid
{

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            array(
                'header' => $this->__('id'),
                'align'  => 'right',
                'width'  => '50px',
                'index'  => 'id',
            )
        );

        $this->addColumn(
            'title',
            array(
                'header' => $this->__('Title'),
                'align'  => 'left',
                'index'  => 'title',
            )
        );

        $this->addColumn(
            'event_type',
            array(
                'header'  => $this->__('Event type'),
                'align'   => 'left',
                // 'width'   => '150px',
                'index'   => 'event_type',
                'type'    => 'options',
                'options' => IWE_EmailReminder_Model_Source_Rule_Types::toShortOptionArray()
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_ids',
                array(
                    'header'                    => $this->__('Store'),
                    'index'                     => 'store_ids',
                    'type'                      => 'store',
                    'store_all'                 => true,
                    'store_view'                => true,
                    'sortable'                  => false,
                    'filter_condition_callback' => array($this, '_filterStoreCondition'),
                )
            );
        }

        $this->addColumn(
            'product_type_ids',
            array(
                'header'                    => $this->__('Product type'),
                'align'                     => 'left',
                'width'                     => '150px',
                'index'                     => 'product_type_ids',
                'options'                   => Mage::getModel('followupemail/source_product_types')
                        ->toShortOptionArray(),
                'filter_condition_callback' => array($this, '_filterProductTypeCondition'),
                'value_separator'           => ',',
                'line_separator'            => '<br>',
                'renderer'                  => 'AW_Followupemail_Block_Adminhtml_Rule_Grid_Column_Multiselect',
            )
        );

        $this->addColumn(
            'status',
            array(
                'header'  => $this->__('Status'),
                'align'   => 'left',
                'width'   => '80px',
                'index'   => 'is_active',
                'type'    => 'options',
                'options' => Mage::getModel('followupemail/source_rule_status')->toOptionArray()
            )
        );

        $this->addColumn(
            'sale_amount',
            array(
                'header' => $this->__('Sale amount'),
                'align'  => 'left',
                'width'  => '80px',
                'index'  => 'sale_amount',
            )
        );

        $this->addColumn(
            'action',
            array(
                'header'    => $this->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption' => $this->__('Edit'),
                        'url'     => array('base' => '*/*/edit'),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
            )
        );

        return $this;
    }
}