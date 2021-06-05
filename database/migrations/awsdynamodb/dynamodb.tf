resource "aws_dynamodb_table" "paylesscms_dynamodb_table" {
    name = "PayLessCMS_Posts"

    hash_key = "PK"
    range_key = "SK"

    billing_mode = "PROVISIONED"
    read_capacity = 5
    write_capacity = 5
    stream_enabled = false

    tags = {}
    tags_all = {}

    attribute {
        name = "PK"
        type = "S"
    }
    attribute {
        name = "SK"
        type = "S"
    }
    attribute {
        name = "GSI1PK"
        type = "S"
    }
    attribute {
        name = "GSI1SK"
        type = "S"
    }

    global_secondary_index {
        name = "GSI1"

        hash_key = "GSI1PK"
        range_key = "GSI1SK"

        non_key_attributes = []
        projection_type = "ALL"

        read_capacity = 5
        write_capacity = 5
    }

    point_in_time_recovery {
        enabled = false
    }

    timeouts {}

    ttl {
        enabled = false
        attribute_name = ""
    }
}
