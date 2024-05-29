<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByIntegrationSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

/**
 * The factory to create FedEx shipment validation request.
 * @deprecated. Will be removed when SOAP support will be dropped by FedEx.
 */
class FedexRateServiceValidateConnectionRequestSoapFactory implements FedexRequestByIntegrationSettingsFactoryInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    /**
     * @var ShippingOriginProvider
     */
    private $shippingOriginProvider;

    public function __construct(
        SymmetricCrypterInterface $crypter,
        ShippingOriginProvider $shippingOriginProvider
    ) {
        $this->crypter = $crypter;
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function create(FedexIntegrationSettings $settings): FedexRequestInterface
    {
        $shippingOrigin = $this->shippingOriginProvider->getSystemShippingOrigin();

        return new FedexRequest(
            '',
            [
                'WebAuthenticationDetail' => [
                    'UserCredential' => [
                        'Key' => $settings->getKey(),
                        'Password' => $this->crypter->decryptData($settings->getPassword()),
                    ]
                ],
                'ClientDetail' => [
                    'AccountNumber' => $settings->getAccountNumberSoap(),
                    'MeterNumber' => $settings->getMeterNumber(),
                ],
                'Version' => [
                    'ServiceId' => 'crs',
                    'Major' => '20',
                    'Intermediate' => '0',
                    'Minor' => '0'
                ],
                'RequestedShipment' => [
                    'DropoffType' => $settings->getPickupTypeSoap(),
                    'Shipper' => [
                        'Address' => [
                            'StreetLines' => [
                                $shippingOrigin->getStreet(),
                            ],
                            'City' => $shippingOrigin->getCity(),
                            'StateOrProvinceCode' => $shippingOrigin->getRegionCode(),
                            'PostalCode' => $shippingOrigin->getPostalCode(),
                            'CountryCode' => $shippingOrigin->getCountryIso2(),
                        ],
                    ],
                    'Recipient' => [
                        'Address' => [
                            'StreetLines' => [
                                $shippingOrigin->getStreet(),
                            ],
                            'City' => $shippingOrigin->getCity(),
                            'StateOrProvinceCode' => $shippingOrigin->getRegionCode(),
                            'PostalCode' => $shippingOrigin->getPostalCode(),
                            'CountryCode' => $shippingOrigin->getCountryIso2(),
                        ],
                    ],
                    'PackageCount' => 1,
                    'RequestedPackageLineItems' => [
                        'GroupPackageCount' => 1,
                        'Weight' => [
                            'Value' => '10',
                            'Units' => $settings->getUnitOfWeight(),
                        ],
                        'Dimensions' => [
                            'Length' => '5',
                            'Width' => '10',
                            'Height' => '10',
                            'Units' => $settings->getDimensionsUnit(),
                        ],
                    ],
                ],
            ]
        );
    }
}
