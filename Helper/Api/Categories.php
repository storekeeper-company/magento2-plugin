<?php

namespace StoreKeeper\StoreKeeper\Helper\Api;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Parsedown;

class Categories extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        Auth $authHelper,
        CategoryFactory $categoryFactory,
        CategoryRepository $categoryRepository,
        CollectionFactory $categoryCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->authHelper = $authHelper;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;


        $this->storeShopIds = $this->authHelper->getStoreShopIds();
        $this->websiteShopIds = $this->authHelper->getWebsiteShopIds();
    }

    public function listCategories(
        $storeId,
        int $offset,
        int $limit,
        array $order,
        array $filters
    ) {
        return $this->authHelper->getModule('ProductsModule', $storeId)->listCategories(
            $storeId,
            $offset,
            $limit,
            $order,
            $filters
        );
    }

    public function updateById($storeId, $storeKeeperId)
    {
        $results = $this->authHelper->getModule('ProductsModule', $storeId)->listCategories(
            0,
            1,
            array(
                array(
                    'name' => 'category_type/id',
                    'dir' => 'asc'
                )
            ),
            [
                [
                    "name" => "id__=",
                    'val' => (string) $storeKeeperId
                ]
            ]
        );

        try {
            if (isset($results['data']) && count($results['data']) > 0) {
                $result = $results['data'][0];
                if ($category = $this->exists($storeId, $result)) {
                    $this->update($storeId, $category, $result);
                } else {
                    $this->create($storeId, $result);
                }
            } else {
                echo 'does not eixst';
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public function exists($storeId, array $result)
    {
        $storekeeper_id = $this->getResultStoreKeeperId($result);
var_dump("storekeeper id = {$storekeeper_id}");
        $collection = $this->categoryCollectionFactory->create();
        $collection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('storekeeper_category_id', $storekeeper_id)
            ->setFlag('has_stock_status_filter', false);

        if ($collection->count() > 0) {
            return $collection->getFirstItem();
        }

        return false;
    }

    public function parentExists($storeId, array $result)
    {

        if ($storekeeper_parent_id = $this->getResultParentId($result)) {

            $collection = $this->categoryCollectionFactory->create();
            $collection
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('storekeeper_category_id', $storekeeper_parent_id)
                ->setFlag('has_stock_status_filter', false);

            if ($collection->count() > 0) {
                return $collection->getFirstItem();
            }
        }

        return false;
    }

    public function create($storeId, array $result)
    {
        return $this->update($storeId, null, $result);
    }

    public function update($storeId, $target = null, array $result)
    {
        $shouldUpdate = false;

        $storekeeper_id = $this->getResultStoreKeeperId($result);
        $title = $result['title'];
        $slug = $result['slug'];


        $update = !is_null($target);
        $create = !$update;

        if ($update) {
            $target = $this->categoryFactory->create()->load($target->getId());
        } else {
            $shouldUpdate = true;
            $target = $this->categoryFactory->create();
        }

        $target->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

        if ($target->getName() != $title) {
            $shouldUpdate = true;
            $target->setName($title);
        }

        $parseDown = new Parsedown();
        $newDescription = $parseDown->text($result['description'] ?? null);

        $newDescription = <<<HTML
<style>
  #html-body [data-pb-style=H1A4J0C] {
    justify-content:flex-start;
    display:flex;
    flex-direction:column;
    background-position:left top;
    background-size:cover;
    background-repeat:no-repeat;
    background-attachment:scroll
  }
</style>
<div data-content-type="row" data-appearance="contained" data-element="main">
  <div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="H1A4J0C">
    <div data-content-type="text" data-appearance="default" data-element="main">
        <p>test</p>
      $newDescription
    </div>
  </div>
</div>
HTML;

        if ($target->getDescription() != $newDescription) {
            $shouldUpdate = true;
            $target->setDescription($newDescription);
        }

        $published = $result['published'] ?? false;
        $isActive = $target->getIsActive();

        if (is_null($isActive)) {
            $target->setIsActive($published);
        } else if ($isActive && !$published) {
            $shouldUpdate = true;
            $target->setIsActive(false);
        } else if (!$isActive && $published) {
            $shouldUpdate = true;
            $target->setIsActive(true);
        }

        $storeKeeperCategoryIdAttribute = $target->getCustomAttribute('storekeeper_category_id');
        if (
            empty($storeKeeperCategoryIdAttribute) ||
            $storeKeeperCategoryIdAttribute->getValue() != $storekeeper_id
        ) {
            $shouldUpdate = true;
            $target->setCustomAttribute('storekeeper_category_id', $storekeeper_id);
        }

        $shouldMove = false;
        $parent = null;

        if ($parent = $this->parentExists($storeId, $result)) {

            if ($target->getParentId() != $parent->getId()) {
                $shouldUpdate = true;
                $shouldMove = true;
            }
        }

        $seo_title = $result['seo_title'] ?? null;
        if ($target->getMetaTitle() != $seo_title) {
            $shouldUpdate = true;
            $target->setMetaTitle($seo_title);
        }

        $seo_description = $result['seo_description'] ?? null;
        if ($target->getMetaDescription() != $seo_description) {
            $shouldUpdate = true;
            $target->setMetaDescription($seo_description);
        }

        $seo_keywords = $result['seo_keywords'] ?? null;
        if ($target->getMetaKeywords() != $seo_keywords) {
            $shouldUpdate = true;
            $target->setMetaKeyswords($seo_keywords);
        }

        if ($shouldUpdate) {

            if ($shouldMove && $update) {
                // categories can only be moved if they exist
                if ($parent) {
                    $target->move($parent->getId(), null);
                }
            }

            if ($update) {
                $this->storeManager->setCurrentStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
            }

            $target = $this->categoryRepository->save($target);

            if ($shouldMove && $create) {
                // categories can only be moved if they exist
                if ($parent) {
                    $target->move($parent->getId(), null);
                    $target = $this->categoryRepository->save($target);
                }
            }

            if ($update) {
                echo "  Updated {$title}\n";
            } else {
                echo "  Created {$title}\n";
            }
        } else {
            echo "  Skipped {$title}, no changes\n";
        }
    }

    private function getResultStoreKeeperId(array $result)
    {
        return $result['id'];
    }

    private function getResultParentId(array $result)
    {
        return $result['parent_id'] ?? false;
    }
}
