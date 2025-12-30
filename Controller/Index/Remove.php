<?php
namespace DevScripts\ProfilePicture\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Filesystem;
// Fix: Corrected the namespace path below
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class Remove implements HttpGetActionInterface {
    
    public function __construct(
        private Session $session,
        private Filesystem $filesystem,
        private CustomerResource $customerResource,
        private ManagerInterface $messageManager,
        private RedirectFactory $resultRedirectFactory
    ) {}

    public function execute() {
        $resultRedirect = $this->resultRedirectFactory->create();
        
        if (!$this->session->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        try {
            $customerId = $this->session->getCustomerId();
            $customer = $this->session->getCustomer();
            $fileName = $customer->getData('profile_picture');

            if ($fileName) {
                // 1. Delete the physical file from pub/media
                $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                $filePath = 'customer/profile/' . $fileName;
                
                if ($mediaDirectory->isExist($filePath)) {
                    $mediaDirectory->delete($filePath);
                }

                // 2. Update Database Column to NULL
                $connection = $this->customerResource->getConnection();
                $tableName = $this->customerResource->getTable('customer_entity');
                $connection->update(
                    $tableName,
                    ['profile_picture' => null],
                    ['entity_id = ?' => $customerId]
                );

                // 3. Clear the session data so the UI updates immediately
                $customer->setData('profile_picture', null);
                
                $this->messageManager->addSuccessMessage(__('Profile picture has been removed.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while removing the image.'));
        }

        return $resultRedirect->setPath('customer/account');
    }
}