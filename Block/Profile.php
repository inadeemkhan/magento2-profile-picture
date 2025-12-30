<?php
namespace DevScripts\ProfilePicture\Block;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Profile extends Template
{
    public function __construct(
        Template\Context $context,
        private Session $customerSession,
        private StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Profile Image URL with fallback to default avatar
     */
    public function getProfilePicture()
    {
        $customer = $this->customerSession->getCustomer();
        $fileName = $customer->getData('profile_picture');

        if ($fileName) {
            return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) 
                . 'customer/profile/' . $fileName;
        }

        // Return default placeholder image from the module's web folder
        return $this->getViewFileUrl('DevScripts_ProfilePicture::images/avatar.png');
    }

    protected function _prepareLayout()
    {
        $this->pageConfig->addPageAsset('DevScripts_ProfilePicture::css/profile.css');
        return parent::_prepareLayout();
    }

    public function hasCustomPicture()
{
    $customer = $this->customerSession->getCustomer();
    return (bool)$customer->getData('profile_picture');
}
}