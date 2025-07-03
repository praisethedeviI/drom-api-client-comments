# Api client implementation with some abstract comments service

This client uses PSR-17 and PSR-18 interfaces to perform requests to some abstract comments service

For local use add this following part to `"repositories"` section in your project's `composer.json`:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../api-client-comments-service"
        }
    ]
}
```
Replace the `"url"` with the actual path to the local package directory if different

---

Then require praisethedevil/api-client-comments-service:dev-{branch}. For example:

```bash
composer require praisethedevil/api-client-comments-service:dev-master
```