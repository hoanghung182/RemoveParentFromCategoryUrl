<?php
declare(strict_types=1);

namespace HungHoang\UpdateCategoryValue\Override\Model;

use Magento\Catalog\Model\Category;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator as MagentoCategoryUrlPathGenerator;

/**
 * Class CategoryUrlPathGenerator
 *
 * @package  HungHoang\UpdateCategoryValue\Override\Model
 * @author   Hung Hoang <hoangvanhung182@gmail.com>
 */
class CategoryUrlPathGenerator extends MagentoCategoryUrlPathGenerator
{
    /**
     * Define whether we should generate URL path for parent
     *
     * @param Category $category
     *
     * @return bool
     */
    protected function isNeedToGenerateUrlPathForParent($category): bool
    {
        if ($category->getData('is_ulmod_short_cat_url')) {
            return false;
        }
        return parent::isNeedToGenerateUrlPathForParent($category);
    }
}
