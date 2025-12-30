<?php
namespace DevScripts\ProfilePicture\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\RequestInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;

class SaveProfileImageObserver implements ObserverInterface
{
    public function __construct(
        private RequestInterface $request,
        private UploaderFactory $uploaderFactory,
        private Filesystem $filesystem,
        private CustomerResource $customerResource
    ) {}

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $files = $this->request->getFiles('profile_picture');

        if ($files && isset($files['name']) && !empty($files['name'])) {
            try {
                $uploader = $this->uploaderFactory->create(['fileId' => 'profile_picture']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                $uploader->setAllowRenameFiles(true);

                $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('customer/profile/');
                $result = $uploader->save($mediaDirectory);

                // Save filename to database
                $connection = $this->customerResource->getConnection();
                $connection->update(
                    $this->customerResource->getTable('customer_entity'),
                    ['profile_picture' => $result['file']],
                    ['entity_id = ?' => $customer->getId()]
                );
            } catch (\Exception $e) {
                // Fail silently or log error
            }
        }
    }
}