export const statusLabels = { up: 'Up', down: 'Down', unknown: 'Unknown' };
export const errorLabels = {
    blocked_target: 'Destination blocked: public endpoints only',
    dns_failure: 'DNS lookup failed',
    dns_timeout: 'DNS lookup timed out',
    curl_unavailable: 'cURL is unavailable on the worker',
    connection_failed: 'Connection failed',
    timeout: 'Request timed out',
    tls_failure: 'TLS verification or connection failed',
    response_too_large: 'Response exceeded 64 KiB',
    redirect_blocked: 'Redirect not followed',
    access_restricted: 'Server responds, but access is protected (401/403). Application health is unverified.',
    redirect_limit: 'Redirect limit reached',
    redirect_loop: 'Redirect loop detected',
    redirect_missing_location: 'Redirect response has no usable Location header',
    invalid_redirect: 'Redirect destination is invalid',
    http_error: 'Unexpected HTTP status',
    request_failed: 'HTTP transfer failed',
};

export function formatCheckDate(value, locale) {
    return new Date(value).toLocaleString(locale);
}
