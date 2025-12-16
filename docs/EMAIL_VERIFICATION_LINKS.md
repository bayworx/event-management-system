# Email Verification Links Configuration

## Overview

Email verification links are generated when attendees register for events. These links allow attendees to verify their email address and gain access to event materials.

## How It Works

When an attendee registers:
1. A unique verification token is generated
2. A verification email is sent with a link to verify their email
3. The link points to: `/event/{slug}/verify/{token}`
4. When clicked, the token is validated and the attendee is verified and logged in

## URL Generation Configuration

### Router Default URI

For emails sent from CLI commands (or when no HTTP request context is available), Symfony needs to know what base URL to use for generating absolute URLs.

This is configured in `config/packages/routing.yaml`:

```yaml
framework:
    router:
        default_uri: '%env(default:default_uri:ROUTER_DEFAULT_URI)%'
```

### Environment Configuration

#### Development (`.env`)
```bash
ROUTER_DEFAULT_URI=http://localhost:8000
```

#### Production (`.env.prod` or server environment)
```bash
ROUTER_DEFAULT_URI=https://yourdomain.com
```

### Default Fallback

If `ROUTER_DEFAULT_URI` is not set, it falls back to the parameter defined in `config/services.yaml`:

```yaml
parameters:
    default_uri: 'http://localhost:8000'
```

## Testing URL Generation

Use the test command to verify URLs are being generated correctly:

```bash
bin/console app:test-url
```

This will show:
- Relative Path: `event/test-event/verify/abc123`
- Absolute Path: `/event/test-event/verify/abc123`
- Absolute URL: `http://localhost:8000/event/test-event/verify/abc123`

## Common Issues

### Issue: Links go to `http://localhost` without port

**Cause**: `ROUTER_DEFAULT_URI` not configured or missing port number

**Solution**: 
1. Check `.env` file has `ROUTER_DEFAULT_URI=http://localhost:8000`
2. Clear cache: `bin/console cache:clear`

### Issue: Links have wrong domain in production

**Cause**: `ROUTER_DEFAULT_URI` still set to localhost

**Solution**:
1. Update environment variable on server:
   ```bash
   ROUTER_DEFAULT_URI=https://yourdomain.com
   ```
2. Or update `.env.prod.local` (not committed):
   ```bash
   ROUTER_DEFAULT_URI=https://yourdomain.com
   ```
3. Clear cache

### Issue: Links work in dev but not production

**Cause**: Different URL configurations between environments

**Solution**:
- Dev uses `http://localhost:8000`
- Production should use your actual domain: `https://yourdomain.com`
- Make sure to set the environment variable on your production server

## Email Template

The verification link is included in the email template at:
`templates/emails/verification.html.twig`

```twig
<a href="{{ verify_url }}" class="button">
    🔐 Verify Email & Complete Registration
</a>
```

## Route Definition

The verification route is defined in `SecurityController`:

```php
#[Route('/event/{slug}/verify/{token}', name: 'attendee_email_verify')]
public function verifyEmail(
    string $slug,
    string $token,
    // ...
): Response
```

## Security Considerations

1. **Token Expiration**: Consider implementing token expiration (currently tokens don't expire)
2. **One-Time Use**: Tokens are cleared after successful verification
3. **Slug Validation**: Route validates that token belongs to the specified event
4. **HTTPS**: Always use HTTPS in production for security

## Deployment Checklist

When deploying to a new environment:

- [ ] Set `ROUTER_DEFAULT_URI` environment variable
- [ ] Use HTTPS for production (`https://yourdomain.com`)
- [ ] Include port only if needed (standard ports 80/443 don't need it)
- [ ] Test with `bin/console app:test-url`
- [ ] Send test registration email and verify link works
- [ ] Clear cache after configuration changes

## Examples

### Local Development
```bash
ROUTER_DEFAULT_URI=http://localhost:8000
# URL: http://localhost:8000/event/my-event/verify/abc123
```

### Staging Server
```bash
ROUTER_DEFAULT_URI=https://staging.example.com
# URL: https://staging.example.com/event/my-event/verify/abc123
```

### Production Server
```bash
ROUTER_DEFAULT_URI=https://events.example.com
# URL: https://events.example.com/event/my-event/verify/abc123
```

### Custom Port (if needed)
```bash
ROUTER_DEFAULT_URI=http://example.com:8080
# URL: http://example.com:8080/event/my-event/verify/abc123
```

## Troubleshooting

### Check Current Configuration

```bash
# View current router configuration
bin/console debug:config framework router

# Test URL generation
bin/console app:test-url

# Check environment variables
bin/console debug:container --env-vars | grep ROUTER
```

### Verify Email Was Sent

```bash
# Check messenger queue (if using async)
bin/console messenger:stats

# View logs for email sending
tail -f var/log/dev.log | grep -i email

# Check if emails are queued
bin/console messenger:failed:show
```

### Re-send Verification Email

Users can request a new verification email from the event page using the "Already Registered?" link, which will generate a new token and send a fresh email.

## Related Files

- `config/packages/routing.yaml` - Router configuration
- `config/services.yaml` - Default parameters
- `.env` - Environment variables
- `src/Controller/EventController.php` - Registration and email sending
- `src/Controller/SecurityController.php` - Verification route
- `templates/emails/verification.html.twig` - Email template
- `src/Command/TestUrlCommand.php` - URL generation test command

## See Also

- [Email Troubleshooting Guide](EMAIL_TROUBLESHOOTING.md)
- [Symfony Router Documentation](https://symfony.com/doc/current/routing.html#generating-urls-in-commands)
