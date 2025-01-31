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
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthConfig;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthType;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    // 設定対象ページ情報
    private $pages = [
        ['plg_customer_2fa_sms_send_onetime', 'SMS認証送信先入力', 'TwoFactorAuthCustomer42/Resource/template/default/tfa/sms/send'],
        ['plg_customer_2fa_sms_input_onetime', 'SMS認証トークン入力', 'TwoFactorAuthCustomer42/Resource/template/default/tfa/sms/input'],
    ];

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        $this->createConfig($em);

        // twigファイルを追加
        $this->copyTwigFiles($container);

        // ページ登録
        $this->createPages($em);
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        // ２段階認証設定を消す
        $this->removeConfig($em);

        // twigファイルを削除
        $this->removeTwigFiles($container);

        // ページ削除
        $this->removePages($em);
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        // ２段階認証設定を消す
        $this->removeConfig($em);

        // twigファイルを削除
        $this->removeTwigFiles($container);

        // ページ削除
        $this->removePages($em);
    }

    /**
     * 設定の登録.
     *
     * @param EntityManagerInterface $em
     */
    protected function createConfig(EntityManagerInterface $em)
    {
        /** @var TwoFactorAuthType $TwoFactorAuthType */
        $TwoFactorAuthType = $em->getRepository(TwoFactorAuthType::class)->findOneBy(['name' => 'SMS']);
        if (!$TwoFactorAuthType) {
            // レコードを保存
            $TwoFactorAuthType = new TwoFactorAuthType();
            $TwoFactorAuthType
                ->setName('SMS')
                ->setRoute('plg_customer_2fa_sms_send_onetime');
        } else {
            // 無効の状態から有効に変更する
            $TwoFactorAuthType->setIsDisabled(false);
        }
        $em->persist($TwoFactorAuthType);

        // 除外ルートの登録
        $TwoFactorAuthConfig = $em->find(TwoFactorAuthConfig::class, 1);
        $em->persist($TwoFactorAuthConfig);
        $em->flush();
    }

    /**
     * Twigファイルの登録
     *
     * @param ContainerInterface $container
     */
    protected function copyTwigFiles(ContainerInterface $container)
    {
        // テンプレートファイルコピー
        $templatePath = $container->get(\Eccube\Common\EccubeConfig::class)->get('eccube_theme_front_dir')
            .'/TwoFactorAuthCustomerSms42/Resource/template/default';
        $fs = new Filesystem();
        if ($fs->exists($templatePath)) {
            return;
        }
        $fs->mkdir($templatePath);
        $fs->mirror(__DIR__.'/Resource/template/default', $templatePath);
    }

    /**
     * ページ情報の登録
     *
     * @param EntityManagerInterface $em
     */
    protected function createPages(EntityManagerInterface $em)
    {
        foreach ($this->pages as $p) {
            $hasPage = $em->getRepository(Page::class)->count(['url' => $p[0]]) > 0;
            if (!$hasPage) {
                /** @var Page $Page */
                $Page = $em->getRepository(Page::class)->newPage();
                $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
                $Page->setUrl($p[0]);
                $Page->setName($p[1]);
                $Page->setFileName($p[2]);
                $Page->setMetaRobots('noindex');

                $em->persist($Page);
                $em->flush();

                $Layout = $em->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
                $PageLayout = new PageLayout();
                $PageLayout->setPage($Page)
                    ->setPageId($Page->getId())
                    ->setLayout($Layout)
                    ->setLayoutId($Layout->getId())
                    ->setSortNo(0);
                $em->persist($PageLayout);
                $em->flush();
            }
        }
    }

    /**
     * ２段階認証設定を消す
     *
     * @param EntityManagerInterface $em
     *
     * @return void
     */
    protected function removeConfig(EntityManagerInterface $em)
    {
        /** @var TwoFactorAuthType|null $TwoFactorAuthType */
        $TwoFactorAuthType = $em->getRepository(TwoFactorAuthType::class)->findOneBy(['name' => 'SMS']);

        // SNSオプションがあれば、そのオプションを無効にする
        if (!empty($TwoFactorAuthType)) {
            $TwoFactorAuthType->setIsDisabled(true);
            $em->persist($TwoFactorAuthType);
        }

        $em->flush();
    }

    /**
     * Twigファイルの削除
     *
     * @param ContainerInterface $container
     */
    protected function removeTwigFiles(ContainerInterface $container)
    {
        $templatePath = $container->get(\Eccube\Common\EccubeConfig::class)->get('eccube_theme_front_dir')
            .'/TwoFactorAuthCustomerSms42';
        $fs = new Filesystem();
        $fs->remove($templatePath);
    }

    /**
     * ページ情報の削除
     *
     * @param EntityManagerInterface $em
     */
    protected function removePages(EntityManagerInterface $em)
    {
        foreach ($this->pages as $p) {
            $Page = $em->getRepository(Page::class)->findOneBy(['url' => $p[0]]);
            if (!$Page) {
                $Layout = $em->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
                $PageLayout = $em->getRepository(PageLayout::class)->findOneBy(['Page' => $Page, 'Layout' => $Layout]);

                $em->remove($PageLayout);
                $em->remove($Page);
                $em->flush();
            }
        }
    }
}
