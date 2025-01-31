<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\TwoFactorAuthCustomerSms42;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\BaseInfo;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\BaseInfoRepository;
use Plugin\TwoFactorAuthCustomer42\Service\CustomerTwoFactorAuthService;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

/**
 * Class Event.
 */
class Event implements EventSubscriberInterface
{
    /**
     * Event constructor.
     *
     */
    public function __construct()
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Customer/edit.twig' => 'onRenderAdminCustomerEdit',
        ];
    }

    /**
     * [/admin/customer/edit]表示の時のEvent Hook.
     * 二段階認証関連項目を追加する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderAdminCustomerEdit(TemplateEvent $event)
    {
        // add twig
        $twig = 'TwoFactorAuthCustomerSms42/Resource/template/admin/customer_edit.twig';
        $event->addSnippet($twig);
    }
}
