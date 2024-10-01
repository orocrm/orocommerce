<?php

namespace Oro\Bundle\PromotionBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Data converter for promotion data.
 */
class CouponDataConverter extends ConfigurableTableDataConverter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        FieldHelper $fieldHelper,
        RelationCalculatorInterface $relationCalculator,
        LocaleSettings $localeSettings,
        TranslatorInterface $translator
    ) {
        parent::__construct($fieldHelper, $relationCalculator, $localeSettings);

        $this->translator = $translator;
    }

    #[\Override]
    protected function convertHeaderToFrontend(array $backendHeader)
    {
        $headers = parent::convertHeaderToFrontend($backendHeader);
        if (array_key_exists('promotion:rule:name', $headers)) {
            $headers['promotion:rule:name'] = $this->translator->trans('oro.promotion.coupon.importexport.promotion');
        }
        return $headers;
    }

    #[\Override]
    protected function convertHeaderToBackend(array $frontendHeader)
    {
        $headers = parent::convertHeaderToBackend($frontendHeader);
        $promotionHeader = $this->translator->trans('oro.promotion.coupon.importexport.promotion');
        if (array_key_exists($promotionHeader, $headers)) {
            $headers[$promotionHeader] = 'promotion:rule:name';
        }
        return $headers;
    }
}
