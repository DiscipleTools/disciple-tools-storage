## ✅ AWS S3 Setup for Disciple.Tools Storage Plugin

### 1. Create an S3 Bucket

1. Go to the [AWS S3 Console](https://console.aws.amazon.com/s3/).
2. Click **“Create bucket”**.
3. Set:

   * **Bucket name**: e.g., `dt-storage`
   * **Region**: Choose your preferred AWS region
4. Under **Block Public Access settings**, leave all boxes **checked** (recommended) – files will be private by default.
5. Leave **Object Lock** unchecked (disabled by default).
6. Click **Create bucket**.

---

### 2. Set CORS Configuration

1. Go to the **Permissions** tab in the bucket.
2. Scroll to **Cross-origin resource sharing (CORS)**.
3. Click **Edit** and paste the following JSON:

```json
[
    {
        "AllowedHeaders": [
            "*"
        ],
        "AllowedMethods": [
            "GET",
            "PUT",
            "POST",
            "DELETE",
            "HEAD"
        ],
        "AllowedOrigins": [
            "https://*.YOURDTDOMAIN.com"
        ],
        "ExposeHeaders": [
            "ETag",
            "x-amz-request-id",
            "x-amz-id-2"
        ],
        "MaxAgeSeconds": 3000
    }
]
```

> ℹ️ Note: AWS does **not support** wildcards (`"*"`) in `ExposeHeaders`. Each header must be listed explicitly.
> Replace `YOURDTDOMAIN` with your actual Disciple.Tools domain.

---

### 4. Create an IAM User

1. Go to the [IAM Console](https://console.aws.amazon.com/iam/).
2. Click **Users > Add user**.
3. Set:

   * **User name**: e.g., `dt-storage-user`
4. When prompted for the **use case**, select:

> ✅ **Application running outside AWS**

5. Click **Next: Permissions**.

---

### 5. Create and Attach a Custom Policy

1. Click **Attach policies directly > Create policy**.
2. In the **JSON** tab, paste:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::dt-storage/*"
    },
    {
      "Effect": "Allow",
      "Action": "s3:ListBucket",
      "Resource": "arn:aws:s3:::dt-storage"
    }
  ]
}
```

> Replace `dt-storage` with your actual bucket name.

3. Click **Next**, give the policy a name (e.g., `DT_S3_Access`), and **create the policy**.
4. Go back to the user setup, click **Refresh**, and select the new policy.
5. Complete the user creation.

---

### 6. Save Access Credentials

Once the IAM user is created:
* Click the user name to view details.
* Under the **Summary** box you will see and Access Key or a button that says **Create access key** to click.
* Copy the **Access Key ID** and **Secret Access Key** ('if you do not copy the secret key now, you will not be able to see it again').
* Store them securely (e.g., password manager).

---

### 7. Configure Disciple.Tools Storage Plugin

In the Disciple.Tools Storage plugin settings:

* **Storage Type**: AWS S3
* **Bucket Name**: `dt-storage` (or your bucket name)
* **Access Key ID**: From IAM user
* **Secret Access Key**: From IAM user
* **Region**: Match the region you selected for the bucket (e.g., `us-east-1`)
* **Endpoint**: the endpoint URL will be in the formate https:/<bucket-name>/s3.<region>.amazonaws.com (IE `https://dt-storage.s3.us-east-1.amazonaws.com` - replace `dt-storage` with your bucket name and `us-east-1` with you S3 bucket region).
