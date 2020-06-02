<?php

/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\Module\BlockWishlist\WishList;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;


class BlockWishlistActionModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (false === $this->context->customer->isLogged()) {
            $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l('You aren\'t logged in', 'mywishlist'),
                ])
            );
            exit;
        }

        $params = Tools::getValue('params');

        $params = [
            'idWishlist' => 3,
            'id_product' => 1,
            'id_product_attribute' => 3,
            'quantity' => 2,
            'id_cart' => 25,
            'name' => 'newWishTestName'
        ];
        // Here we call all methods dinamically given by the path

        if (method_exists($this, Tools::getValue('action') . 'Action')) {
            call_user_func([$this, Tools::getValue('action') . 'Action'], $params);
            exit;
        }

        $this->ajaxRender(
            json_encode([
                'success' => false,
                'message' => $this->module->l('Unknow action', 'mywishlist'),
            ])
        );
        exit;
    }

    private function addProductToWishlistAction($params)
    {
        $id_customer = (int) $this->context->customer->id;
        $idWishlist = (int) $params['idWishlist'];
        $id_product = (int) $params['id_product'];
        $id_product_attribute = (int) $params['id_product_attribute'];
        $quantity = (int) $params['quantity'];

        if (0 !== $idWishlist) {
            if (Wishlist::exists($idWishlist, $this->context->customer->id) === false) {
                $wishlist = new Wishlist();
                $wishlist->id_shop = $this->context->shop->id;
                $wishlist->id_shop_group = $this->context->shop->id_shop_group;
                $wishlist->id_customer = $this->context->customer->id;
                $wishlist->name = 'default';
                $wishlist->token = $this->generateWishlistToken();
                $wishlist->default = 1;
                $wishlist->add();
            }
        } else {
            $wishlist = new Wishlist($idWishlist);
        }

        $productIsAdded = $wishlist->addProduct(
            $idWishlist,
            $this->context->customer->id,
            $id_product,
            $id_product_attribute,
            $quantity
        );

        if (false === $productIsAdded) {
            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l('There was an error adding the product', 'mywishlist'),
                ])
            );
        }

        // TODO: add product to stats

        return $this->ajaxRender(
            json_encode([
                'success' => false,
                'message' => $this->module->l('Product added', 'mywishlist'),
            ])
        );
    }

    private function createNewWishlistAction($params)
    {
        if (isset($params['name'])) {
            $wishlist = new Wishlist();
            $wishlist->name= $params['name'];
            $wishlist->id_shop_group = $this->context->shop->id_shop_group;
            $wishlist->id_customer = $this->context->customer->id;
            $wishlist->id_shop = $this->context->shop->id;
            $wishlist->token = $this->generateWishlistToken();

            if (true === $wishlist->save()) {
                return $this->ajaxRender(
                    json_encode([
                        'status' => 'success',
                        'message' => $this->module->l('The list has been properly created', 'mywishlist'),
                        'datas' => [
                            'name' => $wishlist->name,
                            'id' => $wishlist->id
                        ]
                    ])
                );
            }

            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l('Error saving the new wishlist', 'mywishlist')
                ])
            );
        } else {
            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l('Missing name parameter', 'mywishlist')
                ])
            );
        }
    }

    private function renameWishlistAction($params)
    {
        if (isset($params['idWishlist'], $params['name'])) {
            $wishlist = new Wishlist($params['idWishlist']);
            $wishlist->name = $params['name'];

            if (true === $wishlist->save()) {
                return $this->ajaxRender(
                    json_encode([
                        'success' => true,
                        'message' => $this->module->l('Wishlist has been renamed', 'mywishlist')
                    ])
                );
            }

            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l("Wishlist couldn't been renamed", 'mywishlist')
                ])
            );
        }

        return $this->ajaxRenderMissingParams();
    }

    private function deleteWishlistAction($params)
    {
        if (isset($params['idWishlist'])) {
            $wishlist = new Wishlist($params['idWishlist']);

            if (true === $wishlist->delete()) {
                return $this->ajaxRender(
                    json_encode([
                        'success' => true,
                        'message' => $this->module->l('Wishlist has been removed', 'mywishlist')
                    ])
                );
            }

            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l("Wishlist couldn't been removed", 'mywishlist')
                ])
            );
        }

        return $this->ajaxRenderMissingParams();
    }

    private function deleteProductFromWishlistAction($params)
    {
        if (
            isset($params['idWishlist'])
            && isset($params['id_product'])
            && isset($params['id_product_attribute'])
            && isset($params['id_customer'])
        ) {
            $isDeleted = Wishlist::removeProduct(
                $params['idWishlist'],
                $params['id_customer'],
                $params['id_product'],
                $params['id_product_attribute']
            );

            if (true === $isDeleted) {
                return $this->ajaxRender(
                    json_encode([
                        'success' => true,
                        'message' => $this->module->l('Product succesfully removed', 'mywishlist')
                    ])
                );
            }

            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l('Unable to remove product from wishlist', 'mywishlist')
                ])
            );
        }

        return $this->ajaxRenderMissingParams();
    }

    private function updateProductFromWishlistAction($params)
    {
        if (isset(
            $params['idWishlist'],
            $params['id_product'],
            $params['id_product_attribute'],
            $params['priority'],
            $params['quantity']
        )) {
            $isDeleted = Wishlist::updateProduct(
                $params['idWishlist'],
                $params['id_product'],
                $params['id_product_attribute'],
                $params['priority'],
                $params['quantity']
            );

            if (true === $isDeleted) {
                return $this->ajaxRender(
                    json_encode([
                        'success' => true,
                        'message' => $this->module->l('Product succesfully updated', 'mywishlist')
                    ])
                );
            }

            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l('Unable to update product from wishlist', 'mywishlist')
                ])
            );
        }

        return $this->ajaxRenderMissingParams();
    }

    private function getAllWishlistAction()
    {
        $infos = Wishlist::getAllWishlistsByIdCustomer($this->context->customer->id);

        if (false === empty($infos)) {
            return $this->ajaxRender(
                json_encode([
                    'wishlists' => $infos,
                ])
            );
        }
        return $this->ajaxRenderMissingParams();
    }

    private function generateWishlistToken()
    {
        return strtoupper(substr(sha1(uniqid(rand(), true) . _COOKIE_KEY_ . $this->context->customer->id), 0, 16));
    }

    private function ajaxRenderMissingParams()
    {
        return $this->ajaxRender(
            json_encode([
                'success' => false,
                'message' => $this->module->l('Request is missing one or multiple parameters', 'mywishlist')
            ])
        );
    }

    private function getProductsByWishlistAction($params)
    {
        $params['id_wishlist'] = 3;
        $wishlistProducts = Wishlist::getProductByIdCustomer($params['id_wishlist'], $this->context->customer->id, $this->context->language->id);

        if (empty($wishlistProducts)) {
            return $this->ajaxRender(
                json_encode([
                    'success' => false,
                    'message' => $this->module->l('No products found for this customer', 'mywishlist')
                ])
            );
        }

        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );
        $products_for_template = array();

        if (is_array($wishlistProducts)) {
            foreach ($wishlistProducts as $rawProduct) {
                $products_for_template[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
            }
        }

        return $this->ajaxRender(
            json_encode([
                'status' => 'success',
                'message' => $this->module->l('The list has been properly created', 'mywishlist'),
                'datas' => [
                    'products' => $products_for_template
                ]
            ])
        );
    }

    private function addProductToCartAction($params)
    {
        $productAdd = WishList::addBoughtProduct(
            $params['idWishlist'],
            $params['id_product'],
            $params['id_product_attribute'],
            $params['id_cart'],
            $params['quantity']
        );
        if(true === $productAdd) {
            return $this->ajaxRender(
                json_encode([
                    'status' => 'success',
                    'message' => $this->module->l('Product added to cart'),
                ])
            );
        } else {
            return $this->ajaxRender(
                json_encode([
                    'status' => 'false',
                    'message' => $this->module->l('error when adding product to cart'),
                ])
            );
        }
    }
}
