# Canonical URL Variant Checker

A lightweight PHP-based SEO tool that checks all four common URL variations of a website and identifies whether they redirect to the same final destination.

<img width="1058" height="619" alt="2026-10-31 07 48 08" src="https://github.com/user-attachments/assets/4275de3b-a467-4990-b05a-cdfd41c4e048" />


## Features

- Checks HTTP and HTTPS versions.
- Checks www and non-www versions.
- Automatically follows URL redirects.
- Displays final destination URLs.
- Shows HTTP response status codes.
- Compares URL variations against a canonical reference.
- Detects SSL verification issues.
- Provides a simple, responsive results interface.
- Validates user-entered domains.

## URL Variations Checked

The tool checks these four URL variations:

1. `http://example.com`
2. `https://example.com`
3. `http://www.example.com`
4. `https://www.example.com`

By default, HTTPS non-www is used as the canonical reference when it responds successfully. Otherwise, the tool selects the first working variant.

## Requirements

- PHP 7.4 or later
- PHP cURL extension
- Web server with outgoing HTTP/HTTPS connectivity

## Installation

1. Clone this repository:

   ```bash
   git clone https://github.com/YOUR-USERNAME/canonical-url-variant-checker.git
   ```

2. Navigate to the project directory.

3. Place the PHP file on a PHP-enabled web server.

4. Ensure the PHP cURL extension is enabled.

5. Open the tool in your browser.

## How to Use

1. Enter a domain or website URL.
2. Click **Check Variants**.
3. The tool checks all four URL variations.
4. Review the final destinations, HTTP status codes and match results.
5. Identify URL variations that do not redirect to the selected canonical destination.

## Use Cases

- Technical SEO audits
- Canonical URL consistency checks
- HTTP to HTTPS redirect verification
- WWW and non-WWW redirect testing
- Website migration checks
- Identifying inconsistent URL redirects

## Important Note

This tool compares final redirect destinations. It does not inspect the HTML `rel="canonical"` tag on a webpage. A matching redirect destination does not independently confirm that the page's canonical tag is configured correctly.

## License

MIT License
