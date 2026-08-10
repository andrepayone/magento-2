<?php

/**
 * PAYONE Magento 2 Connector is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PAYONE Magento 2 Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PAYONE Magento 2 Connector. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Block\Adminhtml\Config\Form\Field;

use Payone\Core\Block\Adminhtml\Config\Form\Field\AmazonConfiguration as ClassToTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\View\LayoutInterface;

#[AllowMockObjectsWithoutExpectations]
class AmazonConfigurationTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var bool
     */
    protected $needsObjectManagerMock = true;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, []);
    }

    public function testRender()
    {
        $element = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getHtmlId',
            ])
            ->getMock();

        $element->method('getHtmlId')->willReturn('test');

        $element->setData([
            'label' => 'test',
            'original_data' => [
                'button_label' => 'test'
            ],
            'scope' => true,
            'can_use_website_value' => true,
            'can_use_default_value' => true,
        ]);

        $result = $this->classToTest->render($element);
        $this->assertNotEmpty($result);
        $this->assertFalse($element->hasData('scope'));
        $this->assertFalse($element->hasData('can_use_website_value'));
        $this->assertFalse($element->hasData('can_use_default_value'));
    }

    public function testPrepareLayout()
    {
        $layout = $this->getMockBuilder(LayoutInterface::class)->disableOriginalConstructor()->getMock();

        $this->classToTest->setTemplate(false);

        $result = $this->classToTest->setLayout($layout);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }
}
