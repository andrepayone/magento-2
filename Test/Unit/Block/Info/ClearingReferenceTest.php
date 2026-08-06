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

namespace Payone\Core\Test\Unit\Block\Info;

use Magento\Sales\Model\Order;
use Payone\Core\Block\Info\ClearingReference as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Info;
use Payone\Core\Model\Entities\TransactionStatus;
use Payone\Core\Model\TransactionStatusRepository;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ClearingReferenceTest extends BaseTestCase
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
     * @var Info|\PHPUnit_Framework_MockObject_MockObject
     */
    private $info;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $order->setData([
            'payone_txid' => '12345',
            'payone_clearing_reference' => 'REFERENCE',
        ]);

        $this->info = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->info->setData('order', $order);

        $transactionStatus = $this->getMockBuilder(TransactionStatus::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $transactionStatus->setData([
            'clearing_bankcode' => '12345',
            'clearing_bankaccountholder' => '12345',
            'clearing_bankaccount' => '12345',
            'clearing_bankiban' => '12345',
            'clearing_bankbic' => '12345',
            'clearing_bankname' => '12345',
            'clearing_bankcountry' => 'DE',
            'clearing_bankcity' => 'Berlin',
        ]);

        $transactionStatusRepository = $this->getMockBuilder(TransactionStatusRepository::class)->disableOriginalConstructor()->getMock();
        $transactionStatusRepository->method('getAppointedByTxid')->willReturn($transactionStatus);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'transactionStatusRepository' => $transactionStatusRepository
        ]);
        $this->classToTest->setInfo($this->info);
    }

    public function testPrepareSpecificInformation()
    {
        $this->info->setData('last_trans_id', '12345');

        $expected = [
            'Please transfer the order amount to this bank account' => '',
            'Accountholder' => '12345',
            'IBAN' => '12345',
            'BIC' => '12345',
            'Bank' => '12345',
            'Bank country' => 'DE',
            'Bank city' => 'Berlin',
            'Payone Transaction ID' => '12345',
            'Payment reference' => 'REFERENCE',
        ];

        $result = $this->classToTest->getSpecificInformation();
        $this->assertArrayHasKey('IBAN', $result);
        $this->assertEquals($expected, $result);

        $result = $this->classToTest->getSpecificInformation();
        $this->assertNotEmpty($result);
    }

    public function testPrepareSpecificInformationNoLastTransId()
    {
        $this->info->setData('last_trans_id', '');

        $result = $this->classToTest->getSpecificInformation();
        $this->assertArrayHasKey('Payment has not been processed yet.', $result);
    }
}
