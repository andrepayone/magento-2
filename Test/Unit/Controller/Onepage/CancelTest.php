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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Controller\Onepage;

use Magento\Quote\Model\Quote;
use Payone\Core\Controller\Onepage\Cancel as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Url;
use Magento\Framework\App\RequestInterface;

#[AllowMockObjectsWithoutExpectations]
class CancelTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * Return values for the magic getters of the checkout session
     *
     * @var array
     */
    private $checkoutSessionData = [];

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $resultRedirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $resultRedirect->method('setUrl')->willReturn($resultRedirect);

        $resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $resultFactory->method('create')->willReturn($resultRedirect);

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $request->method('getParam')->willReturn('1');

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getMessageManager')->willReturn($messageManager);
        $context->method('getRequest')->willReturn($request);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('setIsActive')->willReturn($quote);
        $quote->method('setReservedOrderId')->willReturn($quote);
        $quote->method('save')->willReturn($quote);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setQuoteId',
                'getQuote',
                'setLoadInactive',
                'replaceQuote',
                'restoreQuote',
                '__call',
            ])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->checkoutSessionData = [
            'getLastOrderId' => '12345',
            'getPayoneRedirectedPaymentMethod' => 'payone_creditcard',
        ];
        $this->checkoutSession->method('__call')->willReturnCallback(function ($method) {
            if (array_key_exists($method, $this->checkoutSessionData)) {
                return $this->checkoutSessionData[$method];
            }
            return strpos($method, 'get') === 0 ? null : $this->checkoutSession;
        });

        $this->order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->order->method('load')->willReturn($this->order);
        $this->order->method('getQuoteId')->willReturn('12345');
        $this->order->method('cancel')->willReturn($this->order);
        $this->order->method('save')->willReturn($this->order);

        $orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $orderFactory->method('create')->willReturn($this->order);

        $urlBuilder = $this->getMockBuilder(Url::class)->disableOriginalConstructor()->getMock();
        $urlBuilder->method('getUrl')->willReturn('http://test.com');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'orderFactory' => $orderFactory,
            'urlBuilder' => $urlBuilder
        ]);
    }

    public function testExecute()
    {
        $this->checkoutSessionData['getPayoneWorkorderId'] = null;
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecutePayPalExpress()
    {
        $this->checkoutSessionData['getPayoneWorkorderId'] = '12345';
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteException()
    {
        $exception = new \Exception();
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteLocalizedException()
    {
        $exception = new LocalizedException(__('An error occured'));
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
