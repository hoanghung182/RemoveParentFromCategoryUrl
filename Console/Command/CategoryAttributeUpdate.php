<?php
declare(strict_types=1);

namespace HungHoang\UpdateCategoryValue\Console\Command;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Class CategoryAttributeUpdate
 *
 * @category HungHoang
 * @package  HungHoang\UpdateCategoryValue\Console\Command
 * @author   Hung Hoang <hoangvanhung182@gmail.com>
 */
class CategoryAttributeUpdate extends Command
{
    const CAT_ATTRIBUTE_CODE = 'is_ulmod_short_cat_url';
    const CAT_VALUE = 1;

    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        string $name = null
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('update_category')
            ->setDescription('Update value of is_ulmod_short_cat_url')
            ->addArgument('root_category', InputArgument::REQUIRED, 'A root category ID to be updated')
            ->addArgument('store_id', InputArgument::REQUIRED, 'Store ID, to which the root category belongs');
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $rootCategoryId = (int)$input->getArgument('root_category');
        $storeId = (int)$input->getArgument('store_id');

        $categoryCollection = $this->collectionFactory->create();
        $categoryCollection->addIdFilter([$rootCategoryId])
            ->addIsActiveFilter()
            ->addAttributeToSelect(['url_key', 'url_path']);
        if (!$categoryCollection->count()) {
            $output->writeln('No category found with the ID');
            return Cli::RETURN_FAILURE;
        }
        /** @var Category $category */
        foreach ($categoryCollection->getItems() as $category) {
            $this->saveValue($category, $storeId);
        }
        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param Category $category
     * @param          $storeId
     *
     * @return bool
     */
    private function saveValue(Category $category, $storeId): bool
    {
        try {
            if ($category->getIsActive() && !$category->getData(self::CAT_ATTRIBUTE_CODE)) {
                $category->setData(self::CAT_ATTRIBUTE_CODE, self::CAT_VALUE)
                    ->setOrigData('url_path')
                    ->setOrigData('url_key')
                    ->setStoreId($storeId);
                $this->storeManager->setCurrentStore($storeId);
                if ($category->getUrlKey()) {
                    $category->setDataChanges(true);
                    $category->save();
                    $this->output->writeln(sprintf('Updated successfully, ID: %d, name: %s',
                            $category->getId(), $category->getName()
                        )
                    );
                } else {
                    $this->output->writeln(sprintf('URL key is empty. Please update manually and try later, ID: %d, name: %s',
                            $category->getId(), $category->getName()
                        )
                    );
                    $this->logger->error(sprintf('HungHoang - Empty URL key, ID: %d', $category->getId()));
                }
            }

            if ($category->hasChildren()) {
                $childCategories = $category->getChildrenCategories();
                foreach ($childCategories as $item) {
                    $this->saveValue($item, $storeId);
                }
            }
            return true;
        } catch (Exception $e) {
            $this->logger->error(sprintf('HungHoang - Update fail, ID: %d', $category->getId()));
            $this->output->writeln(sprintf('Fail to update, ID: %s', $category->getId()));
            $this->output->writeln(sprintf('Error: %s', $e->getMessage()));
        }
        return false;
    }
}
