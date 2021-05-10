<?php


namespace App\Services;


use Aws\Acm\AcmClient;
use Aws\CloudFront\CloudFrontClient;
use Aws\S3\S3Client;

class AwsHostCreator implements HostCreatorInterface
{
    /**
     * @var S3Client
     */
    private S3Client $s3Client;

    /**
     * @var AcmClient
     */
    private AcmClient $acmClient;

    /**
     * @var CloudFrontClient
     */
    private CloudFrontClient $cloudFrontClient;

    /**
     * Constructor
     *
     * @param S3Client $s3Client
     * @param AcmClient $acmClient
     * @param CloudFrontClient $cloudFrontClient
     */
    public function __construct(S3Client $s3Client, AcmClient $acmClient, CloudFrontClient $cloudFrontClient)
    {
        $this->s3Client = $s3Client;
        $this->acmClient = $acmClient;
        $this->cloudFrontClient = $cloudFrontClient;
    }

    /**
     * Creates a host for the specified site by creating an AWS S3 bucket
     *
     * @param string $site The site to create a host for
     */
    public function createHost(string $site)
    {
        // Create bucket
        $this->createBucket($site);

        // Make bucket static site host
        $this->makeBucketStaticSite($site);

        // Make bucket public
        $this->makeBucketPublic($site);

        // Make bucket read only
        $this->makeBucketReadOnly($site);
    }

    /**
     * Creates a certificate for the site using AWS Certificate Manager
     *
     * @param string $site The site to create a certificate for
     */
    public function createSiteCertificate(string $site)
    {
        // Create the certificate
        $createCertResult = $this->requestAcmCertificate($site);
        $acmCertificateArn = $createCertResult->get('CertificateArn');

//        // Get the certificate
//        $getCertResult = $this->getCertificate($acmCertificateArn);
//        $domainValidationOptions = $getCertResult->get('Certificate')['DomainValidationOptions'];

        return $acmCertificateArn;
    }

    /**
     * Distributes the site using Cloudfront
     *
     * @param string $site The site to distribute across CDN
     * @param array $data Supplemental data
     */
    public function distributeSite(string $site, array $data)
    {
        $bucketName = $site;

        // Create the Cloudfront distribution
        $this->createCloudfrontDistribution($site, $bucketName, $data['AcmCertificateArn']);
    }

    /**
     * Creates a bucket
     *
     * @param string $bucketName
     * @return \Aws\Result
     */
    private function createBucket(string $bucketName)
    {
        return $this->s3Client->createBucket([
            'Bucket' => $bucketName
        ]);
    }

    /**
     * Makes the specified bucket a static site host
     *
     * @param string $bucketName
     * @return \Aws\Result
     */
    private function makeBucketStaticSite(string $bucketName)
    {
        $params = [
            'Bucket' => $bucketName,
            'WebsiteConfiguration' => [
                'ErrorDocument' => [
                    'Key' => 'error.html',
                ],
                'IndexDocument' => [
                    'Suffix' => 'index.html',
                ],
            ]
        ];
        return $this->s3Client->putBucketWebsite($params);
    }

    /**
     * Makes the specified bucket public
     *
     * @param string $bucketName
     * @return \Aws\Result
     */
    private function makeBucketPublic(string $bucketName)
    {
        return $this->s3Client->putPublicAccessBlock([
            'Bucket' => $bucketName,
            'PublicAccessBlockConfiguration' => [
                'BlockPublicAcls' => false,
                'BlockPublicPolicy' => false,
                'IgnorePublicAcls' => false,
                'RestrictPublicBuckets' => false,
            ],
        ]);
    }

    /**
     * Makes the specified bucket read-only
     *
     * @param string $bucketName
     * @return \Aws\Result
     */
    private function makeBucketReadOnly(string $bucketName)
    {
        $policyJson = '{
          "Version": "2012-10-17",
          "Statement": [
            {
              "Sid": "PublicReadGetObject",
              "Effect": "Allow",
              "Principal": "*",
              "Action": "s3:GetObject",
              "Resource": "arn:aws:s3:::[BUCKETNAME]/*"
            }
          ]
        }';
        $policyJson = str_replace("[BUCKETNAME]", $bucketName, $policyJson);
        return $this->s3Client->putBucketPolicy([
            'Bucket' => $bucketName,
            'Policy' => $policyJson
        ]);
    }

    /**
     * Requests a certificate in the AWS Certificate Manager using the specified site
     * as the domain name
     *
     * @param string $site
     * @return \Aws\Result
     */
    private function requestAcmCertificate(string $site)
    {
        return $this->acmClient->requestCertificate([
            //'CertificateAuthorityArn' => '<string>',
            'DomainName' => $site, // REQUIRED
//            'DomainValidationOptions' => [
//                [
//                    'DomainName' => '<string>', // REQUIRED
//                    'ValidationDomain' => '<string>', // REQUIRED
//                ],
//                // ...
//            ],
//            'IdempotencyToken' => '<string>',
//            'Options' => [
//                'CertificateTransparencyLoggingPreference' => 'ENABLED|DISABLED',
//            ],
            'SubjectAlternativeNames' => ['www.' . $site],
//            'Tags' => [
//                [
//                    'Key' => '<string>', // REQUIRED
//                    'Value' => '<string>',
//                ],
//                // ...
//            ],
            'ValidationMethod' => 'DNS',
        ]);
    }

    /**
     * Gets the certificate by its ARN from the AWS Certificate Manager
     *
     * @param string $acmCertificateArn
     * @return \Aws\Result
     */
    private function getCertificate(string $acmCertificateArn)
    {
        return $this->acmClient->describeCertificate([
            'CertificateArn' => $acmCertificateArn
        ]);
    }

    /**
     * Creates a CloudFront distribution using the site as the domain aliases, the S3 bucket as the origin, and the
     * ACM certificate as the SSL certificate
     *
     * @param string $site
     * @param string $bucketName
     * @param string $acmCertificateArn
     * @return \Aws\Result
     */
    private function createCloudfrontDistribution(string $site, string $bucketName, string $acmCertificateArn)
    {
        $originId = 'S3-' . $bucketName;
        $requestPayload = [
            'DistributionConfig' => [ // REQUIRED
                'Aliases' => [
                    'Items' => ['www.' . $site, $site],
                    'Quantity' => 2, // REQUIRED
                ],
//                'CacheBehaviors' => [
//                    'Items' => [
//                        [
//                            'AllowedMethods' => [
//                                'CachedMethods' => [
//                                    'Items' => ['str', ...], // REQUIRED
//                                    'Quantity' => 1, // REQUIRED
//                                ],
//                                'Items' => ['str', ...], // REQUIRED
//                                'Quantity' => 1, // REQUIRED
//                            ],
//                            'CachePolicyId' => 'str',
//                            'Compress' => true || false,
//                            'DefaultTTL' => 1,
//                            'FieldLevelEncryptionId' => 'str',
//                            'ForwardedValues' => [
//                                'Cookies' => [ // REQUIRED
//                                    'Forward' => 'none|whitelist|all', // REQUIRED
//                                    'WhitelistedNames' => [
//                                        'Items' => ['str', ...],
//                                        'Quantity' => 1, // REQUIRED
//                                    ],
//                                ],
//                                'Headers' => [
//                                    'Items' => ['str', ...],
//                                    'Quantity' => 1, // REQUIRED
//                                ],
//                                'QueryString' => true || false, // REQUIRED
//                                'QueryStringCacheKeys' => [
//                                    'Items' => ['str', ...],
//                                    'Quantity' => 1, // REQUIRED
//                                ],
//                            ],
//                            'FunctionAssociations' => [
//                                'Items' => [
//                                    [
//                                        'EventType' => 'viewer-request|viewer-response|origin-request|origin-response', // REQUIRED
//                                        'FunctionARN' => 'str', // REQUIRED
//                                    ],
//                                    // ...
//                                ],
//                                'Quantity' => 1, // REQUIRED
//                            ],
//                            'LambdaFunctionAssociations' => [
//                                'Items' => [
//                                    [
//                                        'EventType' => 'viewer-request|viewer-response|origin-request|origin-response', // REQUIRED
//                                        'IncludeBody' => true || false,
//                                        'LambdaFunctionARN' => 'str', // REQUIRED
//                                    ],
//                                    // ...
//                                ],
//                                'Quantity' => 1, // REQUIRED
//                            ],
//                            'MaxTTL' => 1,
//                            'MinTTL' => 1,
//                            'OriginRequestPolicyId' => 'str',
//                            'PathPattern' => 'str', // REQUIRED
//                            'RealtimeLogConfigArn' => 'str',
//                            'SmoothStreaming' => true || false,
//                            'TargetOriginId' => 'str', // REQUIRED
//                            'TrustedKeyGroups' => [
//                                'Enabled' => true || false, // REQUIRED
//                                'Items' => ['str', ...],
//                                'Quantity' => 1, // REQUIRED
//                            ],
//                            'TrustedSigners' => [
//                                'Enabled' => true || false, // REQUIRED
//                                'Items' => ['str', ...],
//                                'Quantity' => 1, // REQUIRED
//                            ],
//                            'ViewerProtocolPolicy' => 'allow-all|https-only|redirect-to-https', // REQUIRED
//                        ],
//                        // ...
//                    ],
//                    'Quantity' => 1, // REQUIRED
//                ],
                'CallerReference' => $bucketName, // REQUIRED
                'Comment' => '', // REQUIRED
//                'CustomErrorResponses' => [
//                    'Items' => [
//                        [
//                            'ErrorCachingMinTTL' => 1,
//                            'ErrorCode' => 1, // REQUIRED
//                            'ResponseCode' => 'str',
//                            'ResponsePagePath' => 'str',
//                        ],
//                        // ...
//                    ],
//                    'Quantity' => 1, // REQUIRED
//                ],
                'DefaultCacheBehavior' => [ // REQUIRED
                    'AllowedMethods' => [
//                        'CachedMethods' => [
//                            'Items' => ['str', ...], // REQUIRED
//                            'Quantity' => 1, // REQUIRED
//                        ],
                        'Items' => ['GET', 'HEAD'], // REQUIRED
                        'Quantity' => 2, // REQUIRED
                    ],
//                    'CachePolicyId' => 'str',
                    'Compress' => false,
//                    'DefaultTTL' => 1, // DEPRECATED
//                    'FieldLevelEncryptionId' => 'str',
                    'ForwardedValues' => [ // DEPRECATED
                        'Cookies' => [ // REQUIRED
                            'Forward' => 'none', // REQUIRED
//                            'WhitelistedNames' => [
//                                'Items' => ['str', ...],
//                                'Quantity' => 1, // REQUIRED
//                            ],
                        ],
//                        'Headers' => [
//                            'Items' => ['str', ...],
//                            'Quantity' => 1, // REQUIRED
//                        ],
                        'QueryString' => false, // REQUIRED
//                        'QueryStringCacheKeys' => [
//                            'Items' => ['str', ...],
//                            'Quantity' => 1, // REQUIRED
//                        ],
                    ],
//                    'FunctionAssociations' => [
//                        'Items' => [
//                            [
//                                'EventType' => 'viewer-request|viewer-response|origin-request|origin-response', // REQUIRED
//                                'FunctionARN' => 'str', // REQUIRED
//                            ],
//                            // ...
//                        ],
//                        'Quantity' => 1, // REQUIRED
//                    ],
//                    'LambdaFunctionAssociations' => [
//                        'Items' => [
//                            [
//                                'EventType' => 'viewer-request|viewer-response|origin-request|origin-response', // REQUIRED
//                                'IncludeBody' => true || false,
//                                'LambdaFunctionARN' => 'str', // REQUIRED
//                            ],
//                            // ...
//                        ],
//                        'Quantity' => 1, // REQUIRED
//                    ],
//                    'MaxTTL' => 1, // DEPRECATED
                    'MinTTL' => 0, // DEPRECATED
//                    'OriginRequestPolicyId' => 'str',
//                    'RealtimeLogConfigArn' => 'str',
                    'SmoothStreaming' => false,
                    'TargetOriginId' => $originId, // REQUIRED
//                    'TrustedKeyGroups' => [
//                        'Enabled' => true || false, // REQUIRED
//                        'Items' => ['str', ...],
//                        'Quantity' => 1, // REQUIRED
//                    ],
//                    'TrustedSigners' => [
//                        'Enabled' => true || false, // REQUIRED
//                        'Items' => ['str', ...],
//                        'Quantity' => 1, // REQUIRED
//                    ],
                    'ViewerProtocolPolicy' => 'redirect-to-https', // REQUIRED
                ],
                'DefaultRootObject' => 'index.html',
                'Enabled' => true, // REQUIRED
                'HttpVersion' => 'http2',
                'IsIPV6Enabled' => true,
//                'Logging' => [
//                    'Bucket' => 'str', // REQUIRED
//                    'Enabled' => true || false, // REQUIRED
//                    'IncludeCookies' => true || false, // REQUIRED
//                    'Prefix' => 'str', // REQUIRED
//                ],
//                'OriginGroups' => [
//                    'Items' => [
//                        [
//                            'FailoverCriteria' => [ // REQUIRED
//                                'StatusCodes' => [ // REQUIRED
//                                    'Items' => [1, ...], // REQUIRED
//                                    'Quantity' => 1, // REQUIRED
//                                ],
//                            ],
//                            'Id' => 'str', // REQUIRED
//                            'Members' => [ // REQUIRED
//                                'Items' => [ // REQUIRED
//                                    [
//                                        'OriginId' => 'str', // REQUIRED
//                                    ],
//                                    // ...
//                                ],
//                                'Quantity' => 1, // REQUIRED
//                            ],
//                        ],
//                        // ...
//                    ],
//                    'Quantity' => 1, // REQUIRED
//                ],
                'Origins' => [ // REQUIRED
                    'Items' => [ // REQUIRED
                        [
                            'ConnectionAttempts' => 3,
                            'ConnectionTimeout' => 10,
//                            'CustomHeaders' => [
//                                'Items' => [
//                                    [
//                                        'HeaderName' => 'str', // REQUIRED
//                                        'HeaderValue' => 'str', // REQUIRED
//                                    ],
//                                    // ...
//                                ],
//                                'Quantity' => 1, // REQUIRED
//                            ],
//                            'CustomOriginConfig' => [
//                                'HTTPPort' => 1, // REQUIRED
//                                'HTTPSPort' => 1, // REQUIRED
//                                'OriginKeepaliveTimeout' => 1,
//                                'OriginProtocolPolicy' => 'http-only|match-viewer|https-only', // REQUIRED
//                                'OriginReadTimeout' => 1,
//                                'OriginSslProtocols' => [
//                                    'Items' => ['str', ...], // REQUIRED
//                                    'Quantity' => 1, // REQUIRED
//                                ],
//                            ],
                            'DomainName' => $bucketName . '.s3.amazonaws.com', // REQUIRED
                            'Id' => $originId, // REQUIRED
//                            'OriginPath' => 'str',
                            'OriginShield' => [
                                'Enabled' => false, // REQUIRED
//                                'OriginShieldRegion' => 'str',
                            ],
                            'S3OriginConfig' => [
                                'OriginAccessIdentity' => '', // REQUIRED
                            ],
                        ],
                        // ...
                    ],
                    'Quantity' => 1, // REQUIRED
                ],
                'PriceClass' => 'PriceClass_All',
//                'Restrictions' => [
//                    'GeoRestriction' => [ // REQUIRED
//                        'Items' => ['str', ...],
//                        'Quantity' => 1, // REQUIRED
//                        'RestrictionType' => 'blacklist|whitelist|none', // REQUIRED
//                    ],
//                ],
                'ViewerCertificate' => [
                    'ACMCertificateArn' => $acmCertificateArn, // Only one: ACMCertificateArn or IAMCertificateId
//                    'Certificate' => 'str', // DEPRECATED
//                    'CertificateSource' => 'cloudfront|iam|acm', // DEPRECATED
                    'CloudFrontDefaultCertificate' => false,
//                    'IAMCertificateId' => 'str', // Only one: ACMCertificateArn or IAMCertificateId
                    'MinimumProtocolVersion' => 'TLSv1.2_2019',
                    'SSLSupportMethod' => 'sni-only',
                ],
//                'WebACLId' => 'str',
            ],
        ];

        return $this->cloudFrontClient->createDistribution($requestPayload);
    }
}
