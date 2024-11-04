<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads demo data for content templates.
 */
class LoadContentTemplateDemoData implements
    FixtureInterface,
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use UserUtilityTrait;
    use ContainerAwareTrait;

    private const DATA_FILE = '@OroCMSBundle/Migrations/Data/Demo/ORM/data/content_templates.yml';
    private const ASSETS_PATH = '@OroCMSBundle/Migrations/Data/Demo/ORM/data/content-template';
    private const ASSET_IMAGES_PATH = '@OroCMSBundle/Migrations/Data/Demo/ORM/data/content-template/img';
    private const PLACEHOLDER_ASSETS_PATH = DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $path = $this->getFileLocator()->locate(self::ASSETS_PATH);

        $user = $this->getFirstUser($manager);
        $organization = $this->getOrganization($manager);
        $templates = $this->getData();

        $this->setSecurityContext($user, $organization);

        $attachmentArray = $this->loadDigitalAssets($manager);
        $fileManager = $this->container->get('oro_attachment.file_manager');

        foreach ($templates as $template) {
            if (is_file($path . DIRECTORY_SEPARATOR . $template['content'])) {
                $template['content'] = file_get_contents($path . DIRECTORY_SEPARATOR . $template['content']);
            }
            if (is_file($path . DIRECTORY_SEPARATOR . $template['contentStyle'])) {
                $template['contentStyle'] = file_get_contents($path . DIRECTORY_SEPARATOR . $template['contentStyle']);
            }
            if (is_file($path . DIRECTORY_SEPARATOR . $template['previewImage'])) {
                $template['previewImage'] = $this->createFileEntity(
                    $manager,
                    $fileManager,
                    $path . DIRECTORY_SEPARATOR . $template['previewImage']
                );
            } else {
                $template['previewImage'] = null;
            }

            $contentTemplate = new ContentTemplate();
            $contentTemplate->setName($template['name']);
            $contentTemplate->setContent(
                $attachmentArray ? strtr($template['content'], $attachmentArray) : $template['content']
            );
            $contentTemplate->setContentStyle(
                $attachmentArray ? strtr($template['contentStyle'], $attachmentArray) : $template['contentStyle']
            );
            $contentTemplate->setPreviewImage($template['previewImage']);
            $contentTemplate->setOwner($user);
            $contentTemplate->setOrganization($organization);
            $manager->persist($contentTemplate);
            $manager->flush();

            $this->setTags($contentTemplate, $template['tags']);
        }
        $this->container->get('security.token_storage')?->setToken();
    }

    private function loadDigitalAssets(ObjectManager $manager): array
    {
        $digitalAssets = [];
        $fileManager = $this->container->get('oro_attachment.file_manager');
        $fsIterator = new \FilesystemIterator($this->getFileLocator()->locate(self::ASSET_IMAGES_PATH));
        while ($fsIterator->valid()) {
            $file = $fsIterator->current();
            $key =  self::PLACEHOLDER_ASSETS_PATH . $file->getBasename();
            $digitalAsset = $this->createDigitalAsset(
                $manager,
                $fileManager,
                $file->getRealPath(),
                $file->getFilename()
            );
            $digitalAssets[] = [$digitalAsset, $key];
            $fsIterator->next();
        }
        $manager->flush();

        $data = [];
        foreach ($digitalAssets as [$digitalAsset, $key]) {
            $data[$key] = sprintf(
                "{{ wysiwyg_image('%d','%s') }}",
                $digitalAsset->getId(),
                UUIDGenerator::v4()
            );
            $data[$key . '.webp'] = sprintf(
                "{{ wysiwyg_image('%d','%s','wysiwyg_original','webp') }}",
                $digitalAsset->getId(),
                UUIDGenerator::v4()
            );
        }

        return $data;
    }

    private function setTags(ContentTemplate $template, array $tagsArray): void
    {
        $tagManager = $this->getTagManager();
        $tags = new ArrayCollection();
        foreach ($tagsArray as $tag) {
            $tagObj = $tagManager->loadOrCreateTag($tag);
            $tags->add($tagObj);
        }
        $tagManager->setTags($template, $tags);
        $tagManager->saveTagging($template);
    }

    private function getData(): array
    {
        $path = $this->getFileLocator()->locate(self::DATA_FILE);
        $content = file_get_contents($path);

        return Yaml::parse($content);
    }

    private function getTagManager(): TagManager
    {
        return $this->container->get('oro_tag.tag.manager');
    }

    private function getOrganization(ObjectManager $manager): Organization
    {
        return $manager->getRepository(Organization::class)->getFirst();
    }

    private function getFileLocator(): FileLocator
    {
        return $this->container->get('file_locator');
    }

    private function createDigitalAsset(
        ObjectManager $manager,
        FileManager $fileManager,
        string $sourcePath,
        string $title
    ): DigitalAsset {
        $user = $this->getFirstUser($manager);

        $digitalAssetTitle = new LocalizedFallbackValue();
        $digitalAssetTitle->setString($title);
        $manager->persist($digitalAssetTitle);

        $digitalAsset = new DigitalAsset();
        $digitalAsset->addTitle($digitalAssetTitle);
        $digitalAsset->setSourceFile($this->createFileEntity($manager, $fileManager, $sourcePath));
        $digitalAsset->setOwner($user);
        $digitalAsset->setOrganization($user->getOrganization());
        $manager->persist($digitalAsset);

        return $digitalAsset;
    }

    private function createFileEntity(
        ObjectManager $manager,
        FileManager $fileManager,
        string $sourcePath
    ): File {
        $fileEntity = $fileManager->createFileEntity($sourcePath);
        $fileEntity->setOwner($this->getFirstUser($manager));
        $manager->persist($fileEntity);

        return $fileEntity;
    }

    private function setSecurityContext(User $user, Organization $organization): void
    {
        $this->container->get('security.token_storage')->setToken(new UsernamePasswordOrganizationToken(
            $user,
            'main',
            $organization,
            $user->getUserRoles()
        ));
    }
}
