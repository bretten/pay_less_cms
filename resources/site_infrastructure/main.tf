terraform {
    required_providers {
        aws = {
            source = "hashicorp/aws"
            version = "~> 3.27"
        }
    }

    required_version = ">= 0.15.3"
}

provider "aws" {
    profile = "default"
    region = "_REGION_NAME_"
}

provider "aws" {
    alias = "east1"
    region = "us-east-1"
}

locals {
    s3_origin_id = "S3-_SITE_NAME_"
}

resource "aws_s3_bucket" "site_host" {
    bucket = "_SITE_NAME_"
    acl = "private"
    policy = <<EOF
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": [
                "s3:GetObject"
            ],
            "Resource": [
                "arn:aws:s3:::_SITE_NAME_/*"
            ]
        }
    ]
}
EOF

    website {
        index_document = "index.html"
        error_document = "error.html"

        routing_rules = <<EOF
[{
    "Condition": {
        "KeyPrefixEquals": "docs/"
    },
    "Redirect": {
        "ReplaceKeyPrefixWith": "documents/"
    }
}]
EOF
    }

    tags = {
        creator = "paylesscms.com"
    }
}

resource "aws_s3_bucket_public_access_block" "site_host_access_block" {
    bucket = aws_s3_bucket.site_host.id

    block_public_acls = false
    block_public_policy = false
    ignore_public_acls = false
    restrict_public_buckets = false
}

resource "aws_acm_certificate" "site_certificate" {
    provider = aws.east1
    domain_name = "_SITE_NAME_"
    validation_method = "DNS"
    subject_alternative_names = [
        "www._SITE_NAME_"]

    tags = {
        creator = "paylesscms.com"
    }
}

resource "aws_cloudfront_distribution" "site_cdn" {
    origin {
        domain_name = aws_s3_bucket.site_host.bucket_domain_name
        origin_id = local.s3_origin_id
    }

    aliases = [
        "www._SITE_NAME_",
        "_SITE_NAME_"]

    enabled = true
    default_root_object = "index.html"
    http_version = "http2"
    is_ipv6_enabled = true
    price_class = "PriceClass_All"

    default_cache_behavior {
        allowed_methods = [
            "GET",
            "HEAD"]
        cached_methods = [
            "GET",
            "HEAD"]
        compress = false
        default_ttl = 86400
        max_ttl = 31536000
        min_ttl = 0
        smooth_streaming = false
        target_origin_id = local.s3_origin_id
        viewer_protocol_policy = "redirect-to-https"

        forwarded_values {
            headers = []
            query_string = false
            query_string_cache_keys = []

            cookies {
                forward = "none"
                whitelisted_names = []
            }
        }
    }

    restrictions {
        geo_restriction {
            locations = []
            restriction_type = "none"
        }
    }
    viewer_certificate {
        acm_certificate_arn = aws_acm_certificate.site_certificate.arn
        cloudfront_default_certificate = false
        minimum_protocol_version = "TLSv1.2_2019"
        ssl_support_method = "sni-only"
    }

    tags = {
        creator = "paylesscms.com"
    }
}

resource "aws_route53_zone" "site_dns" {
    name = "_SITE_NAME_"
    tags = {
        creator = "paylesscms.com"
    }
}

resource "aws_route53_record" "site_dns_record_alias" {
    name = "_SITE_NAME_"
    type = "A"
    zone_id = aws_route53_zone.site_dns.zone_id

    alias {
        evaluate_target_health = false
        name = aws_cloudfront_distribution.site_cdn.domain_name
        zone_id = aws_cloudfront_distribution.site_cdn.hosted_zone_id
    }
}

resource "aws_route53_record" "site_dns_record_alias_www" {
    name = "www._SITE_NAME_"
    type = "A"
    zone_id = aws_route53_zone.site_dns.zone_id

    alias {
        evaluate_target_health = false
        name = aws_cloudfront_distribution.site_cdn.domain_name
        zone_id = aws_cloudfront_distribution.site_cdn.hosted_zone_id
    }
}

resource "aws_route53_record" "site_dns_record_txt_google_verify" {
    name = "_SITE_NAME_"
    type = "TXT"
    zone_id = aws_route53_zone.site_dns.zone_id
    records = [
        "google-site-verification=",
    ]
    ttl = 300
}
