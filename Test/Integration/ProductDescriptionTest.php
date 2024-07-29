<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ProductDescriptionTest extends TestCase
{
    const FULL_DESCRIPTION = '<style>#html-body [data-pb-style=IQ4798T]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=BMP4ECP]{border-style:none}#html-body [data-pb-style=GB01A2F],#html-body [data-pb-style=P7UX7MQ]{max-width:100%;height:auto}#html-body [data-pb-style=C4Q63GI]{width:100%;border-width:1px;border-color:#cecece;display:inline-block}@media only screen and (max-width: 768px) { #html-body [data-pb-style=BMP4ECP]{border-style:none} }</style><div data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="IQ4798T"><div data-content-type="text" data-appearance="default" data-element="main"><p><strong><span style="font-size: 24px;">Lorem Ipsum</span></strong></p>
<div><br><span style="font-size: 24px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur bibendum auctor turpis vel egestas. Nulla porttitor ac mi eget malesuada. Donec varius est tincidunt tellus venenatis, in rutrum leo tempor. Duis iaculis suscipit turpis sit amet molestie. Ut iaculis tincidunt placerat. Nulla finibus risus dui, ut sodales velit suscipit id. Cras urna libero, sodales in dolor sed, tempor euismod mauris.</span></div>
<div><span style="font-size: 24px;">Vivamus vulputate magna tincidunt urna gravida, in congue nisi molestie.</span></div>
<p>&nbsp;</p>
<p><span style="font-size: 24px;"><strong>Montage richting terugslagklep?</strong></span></p>
<div><br><span style="font-size: 24px;">Duis ac metus placerat, mollis lorem sed, congue ligula. Maecenas blandit tellus id mi interdum, ultrices condimentum felis interdum.&nbsp;</span></div></div><figure data-content-type="image" data-appearance="full-width" data-element="main" data-pb-style="BMP4ECP"><img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/redi_terugslagklep_tekening_1.jpg}}" alt="" title="" data-element="desktop_image" data-pb-style="GB01A2F"><img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/redi_terugslagklep_tekening_1.jpg}}" alt="" title="" data-element="mobile_image" data-pb-style="P7UX7MQ"></figure><div data-content-type="divider" data-appearance="default" data-element="main"><hr data-element="line" data-pb-style="C4Q63GI"></div><div data-content-type="text" data-appearance="default" data-element="main"><p><strong><span style="font-size: 20px;">Mauris rhoncus sem non viverra tincidunt.</span></strong></p></div><div data-content-type="products" data-appearance="grid" data-element="main">{{widget type="Magento\CatalogWidget\Block\Product\ProductsList" template="Magento_CatalogWidget::product/widget/content/grid.phtml" anchor_text="" id_path="" show_pager="0" products_count="5" condition_option="sku" condition_option_value="8716936000381,8715598200214,8715598100309,8716936026725,8712603121649" type_name="Catalog Products List" conditions_encoded="^[`1`:^[`aggregator`:`all`,`new_child`:``,`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`value`:`1`^],`1--1`:^[`operator`:`()`,`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`sku`,`value`:`8716936000381,8715598200214,8715598100309,8716936026725,8712603121649`^]^]" sort_order="position_by_sku"}}</div></div></div>';

    const EXPECTED_DESCRIPTION = '<div data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="IQ4798T"><div data-content-type="text" data-appearance="default" data-element="main"><p><strong><span style="font-size: 24px;">Lorem Ipsum</span></strong></p>
<div><br><span style="font-size: 24px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur bibendum auctor turpis vel egestas. Nulla porttitor ac mi eget malesuada. Donec varius est tincidunt tellus venenatis, in rutrum leo tempor. Duis iaculis suscipit turpis sit amet molestie. Ut iaculis tincidunt placerat. Nulla finibus risus dui, ut sodales velit suscipit id. Cras urna libero, sodales in dolor sed, tempor euismod mauris.</span></div>
<div><span style="font-size: 24px;">Vivamus vulputate magna tincidunt urna gravida, in congue nisi molestie.</span></div>
<p>&nbsp;</p>
<p><span style="font-size: 24px;"><strong>Montage richting terugslagklep?</strong></span></p>
<div><br><span style="font-size: 24px;">Duis ac metus placerat, mollis lorem sed, congue ligula. Maecenas blandit tellus id mi interdum, ultrices condimentum felis interdum.&nbsp;</span></div></div><div data-content-type="divider" data-appearance="default" data-element="main"><hr data-element="line" data-pb-style="C4Q63GI"></div><div data-content-type="text" data-appearance="default" data-element="main"><p><strong><span style="font-size: 20px;">Mauris rhoncus sem non viverra tincidunt.</span></strong></p></div><div data-content-type="products" data-appearance="grid" data-element="main"></div></div></div>';

    protected $productDescription;

    protected function setUp(): void
    {
        $this->productDescription = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Helper\ProductDescription::class);
    }

    public function testFormatProductDescription()
    {
        $actualDescription = $this->productDescription->formatProductDescription(self::FULL_DESCRIPTION);

        //assert of trimmed description
        $this->assertEquals(self::EXPECTED_DESCRIPTION, $actualDescription);
    }

    public function testIsDisallowedContentExist()
    {
        $result = $this->productDescription->isDisallowedContentExist(self::FULL_DESCRIPTION);

        //Assert that provided description has disallowed content
        $this->assertTrue($result);
    }
}
