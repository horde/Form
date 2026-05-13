<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * EmailVariable type for email address input fields.
 *
 * @property bool $allow_multi Allow multiple addresses
 * @property bool $strip_domain Protect address from spammers
 * @property bool $link_compose Link the email address to the compose page when displaying
 * @property bool $check_smtp Whether to check the domain's SMTP server whether the address exists
 * @property string $link_name The name to use when linking to the compose page
 * @property string $delimiters A string containing valid delimiters
 * @property int $size The size of the input field

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_email PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class EmailVariable extends BaseVariable
{
    /**
     * Allow multiple addresses?
     *
     * @var boolean
     */
    public $_allow_multi = false;

    /**
     * Protect address from spammers?
     *
     * @var boolean
     */
    public $_strip_domain = false;

    /**
     * Link the email address to the compose page when displaying?
     *
     * @var boolean
     */
    public $_link_compose = false;

    /**
     * Whether to check the domain's SMTP server whether the address exists.
     *
     * @var boolean
     */
    public $_check_smtp = false;

    /**
     * The name to use when linking to the compose page
     *
     * @var boolean
     */
    public $_link_name;

    /**
     * A string containing valid delimiters (default is just comma).
     *
     * @var string
     */
    public $_delimiters = ',';

    /**
     * The size of the input field.
     *
     * @var integer
     */
    public $_size;

    /**
     * Initialize an email field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: bool $allow_multi - Allow multiple addresses (default: false)
     *                      - $params[1]: bool $strip_domain - Protect address from spammers (default: false)
     *                      - $params[2]: bool $link_compose - Link the email address to the compose page when displaying (default: false)
     *                      - $params[3]: string|null $link_name - The name to use when linking to the compose page (default: null)
     *                      - $params[4]: string $delimiters - Character to split multiple addresses with (default: ',')
     *                      - $params[5]: int|null $size - The size of the input field (default: null)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_allow_multi = $params[0] ?? false;
        $this->_strip_domain = $params[1] ?? false;
        $this->_link_compose = $params[2] ?? false;
        $this->_link_name = $params[3] ?? null;
        $this->_delimiters = $params[4] ?? ',';
        $this->_size = $params[5] ?? null;
    }

    /**
      *
      * @api
     */
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        // Split into individual addresses.
        $emails = $this->splitEmailAddresses($value);

        // Check for too many.
        if (!$this->_allow_multi && count($emails) > 1) {
            return $this->invalid('Only one email address is allowed.');
        }

        // Check for all valid and at least one non-empty.
        $nonEmpty = 0;
        foreach ($emails as $email) {
            if (!strlen($email)) {
                continue;
            }
            if (!$this->validateEmailAddress($email)) {
                $this->message = sprintf(Horde_Form_Translation::t("\"%s\" is not a valid email address."), htmlspecialchars($email));
                return false;
            }
            ++$nonEmpty;
        }

        if (!$nonEmpty && $this->isRequired()) {
            if ($this->_allow_multi) {
                return $this->invalid('You must enter at least one email address.');
            }
            return $this->invalid('You must enter an email address.');
        }

        return true;
    }

    /**
     * Splits RFC 2822 formatted email address string into individual addresses.
     *
     * Parses email address lists, handling quoted strings, escape characters,
     * and group syntax (name:addr1,addr2;). Ignores delimiters inside quotes
     * or preceded by backslash.
     *
     * @param string $string  The RFC 822 formatted email address string
     *
     * @return array  Array of individual email addresses (strings)
      *
      * @api
     */
    public function splitEmailAddresses($string)
    {
        // Trim off any trailing delimiters
        $string = trim($string, $this->_delimiters . ' ');

        $quotes = [ '"', "'" ];
        $emails = [];
        $pos = 0;
        $in_quote = null;
        $in_group = false;
        $prev = null;

        if (!strlen($string)) {
            return [];
        }

        $char = $string[0];
        if (in_array($char, $quotes)) {
            $in_quote = $char;
        } elseif ($char == ':') {
            $in_group = true;
        } elseif (strpos($this->_delimiters, $char) !== false) {
            $emails[] = '';
            $pos = 1;
        }

        for ($i = 1, $iMax = strlen($string); $i < $iMax; ++$i) {
            $char = $string[$i];

            if (in_array($char, $quotes)) {
                if ($prev !== '\\') {
                    if ($in_quote === $char) {
                        $in_quote = null;
                    } elseif (is_null($in_quote)) {
                        $in_quote = $char;
                    }
                }
            } elseif ($in_group) {
                if ($char == ';') {
                    $emails[] = substr($string, $pos, $i - $pos + 1);
                    $pos = $i + 1;
                    $in_group = false;
                }
            } elseif ($char == ':') {
                $in_group = true;
            } elseif (strpos($this->_delimiters, $char) !== false
                      && $prev !== '\\'
                      && is_null($in_quote)) {
                $emails[] = substr($string, $pos, $i - $pos);
                $pos = $i + 1;
            }

            $prev = $char;
        }

        if ($pos != $i) {
            /* The string ended without a delimiter. */
            $emails[] = substr($string, $pos, $i - $pos);
        }

        return $emails;
    }

    /**
     * Validates an email address.
     *
     * Performs RFC 3696 validation on the email address. If SMTP checking
     * is enabled, also attempts SMTP validation.
     *
     * @param string $email  An individual email address to validate
     *
     * @return bool  True if email is valid, false otherwise
      *
      * @api
     */
    public function validateEmailAddress($email)
    {
        $result = $this->_isRfc3696ValidEmailAddress($email);
        if ($result && $this->_check_smtp) {
            $result = $this->validateEmailAddressSmtp($email);
        }
        return $result;
    }

    /**
     * Validates email address via SMTP connection.
     *
     * Attempts partial mail delivery to validate the email address exists.
     * Connects to the mail server via SMTP and checks if RCPT TO command
     * succeeds. Uses MX records if available, falls back to domain A record.
     *
     * @param string $email  An individual email address to validate
     *
     * @return bool  True if SMTP server accepts the address, false otherwise
      *
      * @api
     */
    public function validateEmailAddressSmtp($email)
    {
        [, $maildomain] = explode('@', $email, 2);

        // Try to get the real mailserver from MX records.
        if (function_exists('getmxrr')
            && @getmxrr($maildomain, $mxhosts, $mxpriorities)) {
            // MX record found.
            array_multisort($mxpriorities, $mxhosts);
            $mailhost = $mxhosts[0];
        } else {
            // No MX record found, try the root domain as the mail
            // server.
            $mailhost = $maildomain;
        }

        $fp = @fsockopen($mailhost, 25, $errno, $errstr, 5);
        if (!$fp) {
            return false;
        }

        // Read initial response.
        fgets($fp, 4096);

        // HELO
        fputs($fp, "HELO $mailhost\r\n");
        fgets($fp, 4096);

        // MAIL FROM
        fputs($fp, "MAIL FROM: <root@example.com>\r\n");
        fgets($fp, 4096);

        // RCPT TO - gets the result we want.
        fputs($fp, "RCPT TO: <$email>\r\n");
        $result = trim(fgets($fp, 4096));

        // QUIT
        fputs($fp, "QUIT\r\n");
        fgets($fp, 4096);
        fclose($fp);

        return substr($result, 0, 1) == '2';
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function allowMulti()
    {
        return $this->_allow_multi;
    }

    /**
     * Validates email address according to RFC 3696 specification.
     *
     * Performs comprehensive RFC 3696 validation including:
     * - Local part and domain length limits (64 and 255 chars)
     * - Address format validation (dot-atom, quoted-string, domain-literal)
     * - Domain literal validation (IPv4 and IPv6 addresses)
     * - Label validation (length, hyphens, numeric TLD checks)
     * - Comment stripping and nested comment handling
     *
     * Implementation uses complex regex patterns built from RFC grammar rules
     * to validate all address components. Handles obsolete syntax from RFC 2822
     * and domain literals from RFC 2821.
     *
     * RFC3696 Email Parser
     * By Cal Henderson <cal@iamcal.com>
     *
     * This code is dual licensed:
     * CC Attribution-ShareAlike 2.5 - http://creativecommons.org/licenses/by-sa/2.5/
     * GPLv3 - http://www.gnu.org/copyleft/gpl.html
     *
     * @param string $email  Email address to validate
     *
     * @return int  1 if valid RFC 3696 address, 0 otherwise
      *
      * @internal
     */
    protected function _isRfc3696ValidEmailAddress($email)
    {
        ####################################################################################
        #
        # NO-WS-CTL       =       %d1-8 /         ; US-ASCII control characters
        #                         %d11 /          ;  that do not include the
        #                         %d12 /          ;  carriage return, line feed,
        #                         %d14-31 /       ;  and white space characters
        #                         %d127
        # ALPHA          =  %x41-5A / %x61-7A   ; A-Z / a-z
        # DIGIT          =  %x30-39

        $no_ws_ctl  = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
        $alpha      = "[\\x41-\\x5a\\x61-\\x7a]";
        $digit      = "[\\x30-\\x39]";
        $cr     = "\\x0d";
        $lf     = "\\x0a";
        $crlf       = "(?:$cr$lf)";

        ####################################################################################
        #
        # obs-char        =       %d0-9 / %d11 /          ; %d0-127 except CR and
        #                         %d12 / %d14-127         ;  LF
        # obs-text        =       *LF *CR *(obs-char *LF *CR)
        # text            =       %d1-9 /         ; Characters excluding CR and LF
        #                         %d11 /
        #                         %d12 /
        #                         %d14-127 /
        #                         obs-text
        # obs-qp          =       "\" (%d0-127)
        # quoted-pair     =       ("\" text) / obs-qp

        $obs_char   = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
        $obs_text   = "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
        $text       = "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";

        #
        # there's an issue with the definition of 'text', since 'obs_text' can
        # be blank and that allows qp's with no character after the slash. we're
        # treating that as bad, so this just checks we have at least one
        # (non-CRLF) character
        #

        $text       = "(?:$lf*$cr*$obs_char$lf*$cr*)";
        $obs_qp     = "(?:\\x5c[\\x00-\\x7f])";
        $quoted_pair    = "(?:\\x5c$text|$obs_qp)";

        ####################################################################################
        #
        # obs-FWS         =       1*WSP *(CRLF 1*WSP)
        # FWS             =       ([*WSP CRLF] 1*WSP) /   ; Folding white space
        #                         obs-FWS
        # ctext           =       NO-WS-CTL /     ; Non white space controls
        #                         %d33-39 /       ; The rest of the US-ASCII
        #                         %d42-91 /       ;  characters not including "(",
        #                         %d93-126        ;  ")", or "\"
        # ccontent        =       ctext / quoted-pair / comment
        # comment         =       "(" *([FWS] ccontent) [FWS] ")"
        # CFWS            =       *([FWS] comment) (([FWS] comment) / FWS)
        #
        # note: we translate ccontent only partially to avoid an infinite loop
        # instead, we'll recursively strip *nested* comments before processing
        # the input. that will leave 'plain old comments' to be matched during
        # the main parse.
        #

        $wsp        = "[\\x20\\x09]";
        $obs_fws    = "(?:$wsp+(?:$crlf$wsp+)*)";
        $fws        = "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
        $ctext      = "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
        $ccontent   = "(?:$ctext|$quoted_pair)";
        $comment    = "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
        $cfws       = "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";

        #
        # these are the rules for removing *nested* comments. we'll just detect
        # outer comment and replace it with an empty comment, and recurse until
        # we stop.
        #

        $outer_ccontent_dull    = "(?:$fws?$ctext|$quoted_pair)";
        $outer_ccontent_nest    = "(?:$fws?$comment)";
        $outer_comment      = "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";

        ####################################################################################
        #
        # atext           =       ALPHA / DIGIT / ; Any character except controls,
        #                         "!" / "#" /     ;  SP, and specials.
        #                         "$" / "%" /     ;  Used for atoms
        #                         "&" / "'" /
        #                         "*" / "+" /
        #                         "-" / "/" /
        #                         "=" / "?" /
        #                         "^" / "_" /
        #                         "`" / "{" /
        #                         "|" / "}" /
        #                         "~"
        # atom            =       [CFWS] 1*atext [CFWS]

        $atext      = "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
        $atom       = "(?:$cfws?(?:$atext)+$cfws?)";

        ####################################################################################
        #
        # qtext           =       NO-WS-CTL /     ; Non white space controls
        #                         %d33 /          ; The rest of the US-ASCII
        #                         %d35-91 /       ;  characters not including "\"
        #                         %d93-126        ;  or the quote character
        # qcontent        =       qtext / quoted-pair
        # quoted-string   =       [CFWS]
        #                         DQUOTE *([FWS] qcontent) [FWS] DQUOTE
        #                         [CFWS]
        # word            =       atom / quoted-string

        $qtext      = "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
        $qcontent   = "(?:$qtext|$quoted_pair)";
        $quoted_string  = "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";

        #
        # changed the '*' to a '+' to require that quoted strings are not empty
        #

        $quoted_string  = "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
        $word       = "(?:$atom|$quoted_string)";

        ####################################################################################
        #
        # obs-local-part  =       word *("." word)
        # obs-domain      =       atom *("." atom)

        $obs_local_part = "(?:$word(?:\\x2e$word)*)";
        $obs_domain = "(?:$atom(?:\\x2e$atom)*)";

        ####################################################################################
        #
        # dot-atom-text   =       1*atext *("." 1*atext)
        # dot-atom        =       [CFWS] dot-atom-text [CFWS]

        $dot_atom_text  = "(?:$atext+(?:\\x2e$atext+)*)";
        $dot_atom   = "(?:$cfws?$dot_atom_text$cfws?)";

        ####################################################################################
        #
        # domain-literal  =       [CFWS] "[" *([FWS] dcontent) [FWS] "]" [CFWS]
        # dcontent        =       dtext / quoted-pair
        # dtext           =       NO-WS-CTL /     ; Non white space controls
        #
        #                         %d33-90 /       ; The rest of the US-ASCII
        #                         %d94-126        ;  characters not including "[",
        #                                         ;  "]", or "\"

        $dtext      = "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
        $dcontent   = "(?:$dtext|$quoted_pair)";
        $domain_literal = "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";

        ####################################################################################
        #
        # local-part      =       dot-atom / quoted-string / obs-local-part
        # domain          =       dot-atom / domain-literal / obs-domain
        # addr-spec       =       local-part "@" domain

        $local_part = "(($dot_atom)|($quoted_string)|($obs_local_part))";
        $domain     = "(($dot_atom)|($domain_literal)|($obs_domain))";
        $addr_spec  = "$local_part\\x40$domain";

        #
        # see http://www.dominicsayers.com/isemail/ for details, but this should probably be 254
        #

        if (strlen($email) > 256) {
            return 0;
        }

        #
        # we need to strip nested comments first - we replace them with a simple comment
        #

        $email = $this->_rfc3696StripComments($outer_comment, $email, "(x)");

        #
        # now match what's left
        #

        if (!preg_match("!^$addr_spec$!", $email, $m)) {
            return 0;
        }

        $bits = [
            'local'         => $m[1] ?? '',
            'local-atom'        => $m[2] ?? '',
            'local-quoted'      => $m[3] ?? '',
            'local-obs'     => $m[4] ?? '',
            'domain'        => $m[5] ?? '',
            'domain-atom'       => $m[6] ?? '',
            'domain-literal'    => $m[7] ?? '',
            'domain-obs'        => $m[8] ?? '',
        ];

        #
        # we need to now strip comments from $bits[local] and $bits[domain],
        # since we know they're i the right place and we want them out of the
        # way for checking IPs, label sizes, etc
        #

        $bits['local']  = $this->_rfc3696StripComments($comment, $bits['local']);
        $bits['domain'] = $this->_rfc3696StripComments($comment, $bits['domain']);

        #
        # length limits on segments
        #

        if (strlen($bits['local']) > 64) {
            return 0;
        }
        if (strlen($bits['domain']) > 255) {
            return 0;
        }

        #
        # restrictions on domain-literals from RFC2821 section 4.1.3
        #

        if (strlen($bits['domain-literal'])) {
            $Snum           = "(\d{1,3})";
            $IPv4_address_literal   = "$Snum\.$Snum\.$Snum\.$Snum";

            $IPv6_hex       = "(?:[0-9a-fA-F]{1,4})";

            $IPv6_full      = "IPv6\:$IPv6_hex(:?\:$IPv6_hex){7}";

            $IPv6_comp_part     = "(?:$IPv6_hex(?:\:$IPv6_hex){0,5})?";
            $IPv6_comp      = "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";

            $IPv6v4_full        = "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";

            $IPv6v4_comp_part   = "$IPv6_hex(?:\:$IPv6_hex){0,3}";
            $IPv6v4_comp        = "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";

            #
            # IPv4 is simple
            #

            if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)) {
                if (intval($m[1]) > 255) {
                    return 0;
                }
                if (intval($m[2]) > 255) {
                    return 0;
                }
                if (intval($m[3]) > 255) {
                    return 0;
                }
                if (intval($m[4]) > 255) {
                    return 0;
                }
            } else {
                #
                # this should be IPv6 - a bunch of tests are needed here :)
                #

                while (1) {
                    if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])) {
                        break;
                    }

                    if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)) {
                        [$a, $b] = explode('::', $m[1]);
                        $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                        $groups = explode(':', $folded);
                        if (count($groups) > 6) {
                            return 0;
                        }
                        break;
                    }

                    if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)) {
                        if (intval($m[1]) > 255) {
                            return 0;
                        }
                        if (intval($m[2]) > 255) {
                            return 0;
                        }
                        if (intval($m[3]) > 255) {
                            return 0;
                        }
                        if (intval($m[4]) > 255) {
                            return 0;
                        }
                        break;
                    }

                    if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)) {
                        [$a, $b] = explode('::', $m[1]);
                        $b = substr($b, 0, -1); # remove the trailing colon before the IPv4 address
                        $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                        $groups = explode(':', $folded);
                        if (count($groups) > 4) {
                            return 0;
                        }
                        break;
                    }

                    return 0;
                }
            }
        } else {
            #
            # the domain is either dot-atom or obs-domain - either way, it's
            # made up of simple labels and we split on dots
            #

            $labels = explode('.', $bits['domain']);

            #
            # this is allowed by both dot-atom and obs-domain, but is un-routeable on the
            # public internet, so we'll fail it (e.g. user@localhost)
            #

            if (count($labels) == 1) {
                return 0;
            }

            #
            # checks on each label
            #

            foreach ($labels as $label) {
                if (strlen($label) > 63) {
                    return 0;
                }
                if (substr($label, 0, 1) == '-') {
                    return 0;
                }
                if (substr($label, -1) == '-') {
                    return 0;
                }
            }

            #
            # last label can't be all numeric
            #

            if (preg_match('!^[0-9]+$!', array_pop($labels))) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * Strips RFC 2822 comments from email address string.
     *
     * Recursively removes comments matching the provided regex pattern until
     * no more matches are found. Comments in RFC 2822 format are enclosed in
     * parentheses and can be nested. This method handles nested comments by
     * repeatedly applying the regex replacement until the string stabilizes.
     *
     * Used during RFC 3696 email validation to remove comments from the
     * local part and domain before performing length and format checks.
     *
     * RFC3696 Email Parser
     * By Cal Henderson <cal@iamcal.com>
     *
     * This code is dual licensed:
     * CC Attribution-ShareAlike 2.5 - http://creativecommons.org/licenses/by-sa/2.5/
     * GPLv3 - http://www.gnu.org/copyleft/gpl.html
     *
     * $Revision: 5039 $
     *
     * @param string $comment  Regex pattern matching RFC 2822 comment syntax
     * @param string $email    Email address string to process
     * @param string $replace  Replacement string for matched comments (default: empty string)
     *
     * @return string  Email address with all matching comments removed
      *
      * @internal
     */
    protected function _rfc3696StripComments($comment, $email, $replace = '')
    {
        while (1) {
            $new = preg_replace("!$comment!", $replace, $email);
            if (strlen($new) == strlen($email)) {
                return $email;
            }
            $email = $new;
        }
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Email"),
            'params' => [
                'allow_multi' => [
                    'label' => Horde_Form_Translation::t("Allow multiple addresses?"),
                    'type'  => 'boolean',
                ],
                'strip_domain' => [
                    'label' => Horde_Form_Translation::t("Protect address from spammers?"),
                    'type' => 'boolean',
                ],
                'link_compose' => [
                    'label' => Horde_Form_Translation::t("Link the email address to the compose page when displaying?"),
                    'type' => 'boolean',
                ],
                'link_name' => [
                    'label' => Horde_Form_Translation::t("The name to use when linking to the compose page"),
                    'type' => 'text',
                ],
                'delimiters' => [
                    'label' => Horde_Form_Translation::t("Character to split multiple addresses with"),
                    'type' => 'text',
                ],
                'size' => [
                    'label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int',
                ],
            ],
        ];
    }
}
