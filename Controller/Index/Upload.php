<?php
namespace DevScripts\ProfilePicture\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Customer\Model\Session;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class Upload implements HttpPostActionInterface {
    
    public function __construct(
        private Session $session,
        private UploaderFactory $uploaderFactory,
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
            // 1. Manual File Validation (Fixes the TypeError)
            if (!isset($_FILES['profile_picture']) || empty($_FILES['profile_picture']['name'])) {
                throw new \Exception(__('Please select a valid image file.'));
            }

            // Check Size (2MB = 2097152 bytes)
            if ($_FILES['profile_picture']['size'] > 2097152) {
                throw new \Exception(__('The file you are trying to upload is too large. Max size is 2MB.'));
            }

            // 2. Initialize Uploader
            $uploader = $this->uploaderFactory->create(['fileId' => 'profile_picture']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $path = $mediaDirectory->getAbsolutePath('customer/profile/');
            
            $result = $uploader->save($path);
            $fileName = $result['file'];

            // 3. Update Database Directly
            $customerId = $this->session->getCustomerId();
            $connection = $this->customerResource->getConnection();
            $tableName = $this->customerResource->getTable('customer_entity');

            $connection->update(
                $tableName,
                ['profile_picture' => $fileName],
                ['entity_id = ?' => $customerId]
            );

            // 4. Update session data
            $this->session->getCustomer()->setData('profile_picture', $fileName);

            $this->messageManager->addSuccessMessage(__('Profile picture updated successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('customer/account');
    }
}