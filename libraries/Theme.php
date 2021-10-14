<?php

class Theme
{
    protected $_l_delimiter = '{';
    protected $_r_delimiter = '}';
    protected $_l_delimiter_pattern = '';
    protected $_r_delimiter_pattern = '';
    protected $_cache_file;
    protected $_cache_dir;
    protected $_cache_path;
    protected $_enable_cache;
    public $CI;
    protected $_reserved = array(
        'foreach', 'as', 'if', 'elif', 'else', '/',
    );
    protected $_symbols = array(
        '/', '!', '(', ')', ',', '=', '->',
    );
    protected $_strings = array();
    protected $_str_prefix = 'samsabz_STRING_';
    protected $_phps = array();
    protected $_php_prefix = 'samsabz_PHP_';
    protected $_contents;
    protected $_code_raw;
    protected $_code_parsed;
    protected $_code_raw_parts = array();
    protected $_code_parsed_parts = array();
    protected $_to_unshift = array();
    protected $_to_push = array();
    protected $_config;
    protected $_exclude_patterns = array(
        'false', 'true', 'null', '\d.*',
    );
    protected $_exceptions = array();

    function __construct()
    {
        $this->CI = &get_instance();
        $this->_set_delimiter_patterns();
        $this->CI->config->load('theme', TRUE);
        $this->_config = $this->CI->config->item('theme');
        $this->_cache_dir = rtrim($this->_config['cache_dir'], '/') . '/';
        $this->_check_cache_dir();
        $this->CI->load->add_package_path($this->_cache_dir);
        $this->add_exception(['elapsed_time', 'memory_usage']);
        $this->_enable_cache = isset($this->_config['enable_cache']) ? (bool) $this->_config['enable_cache'] : TRUE;
    }

    public function parse($view, $data = array(), $return = FALSE)
    {
        $this->CI->benchmark->mark('samsabz_parse_start');

        $view_file = VIEWPATH . $view . '.php';

        if (!is_file($view_file) or !is_readable($view_file)) {
            die('Unable to load view: ' . $view);
        }

        $this->_cache_file = md5($view_file);
        $this->_cache_path = $this->_cache_dir . 'views/' . $this->_cache_file . '.php';

        if (!$this->_enable_cache or !$this->_cache_valid($view_file, $this->_cache_path)) {
            $this->_contents = file_get_contents($view_file);
            $this->_preserve_phps();
            $this->_parse();
            $this->_restore_phps();
            file_put_contents($this->_cache_path, $this->_contents);
            //log_message('info', 'samsabz: Parsed view ' . $view);
        } else {
            //log_message('info', 'samsabz: Built view ' . $view . ' from cache');
        }

        $output = $this->CI->load->view($this->_cache_file, $data, TRUE);

        if (!$this->_enable_cache) {
            unlink($this->_cache_path);
        }

        $this->CI->benchmark->mark('samsabz_parse_end');
        if ($return) {
            return $output;
        }
        $this->CI->output->append_output($output);
    }

    protected function _cache_exists($cache_path)
    {
        if (is_file($cache_path)) {
            return TRUE;
        }
        return FALSE;
    }

    protected function _cache_valid($view_path, $cache_path)
    {
        if (!$this->_cache_exists($cache_path)) {
            return FALSE;
        }
        $view_mtime = filemtime($view_path);
        $cache_mtime = filemtime($cache_path);
        return $cache_mtime >= $view_mtime;
    }

    protected function _parse()
    {
        $this->_contents = preg_replace_callback(
            $this->_l_delimiter_pattern . '(.*?)' . $this->_r_delimiter_pattern,
            array($this, '_process'),
            $this->_contents
        );
    }

    protected function _preserve_phps()
    {
        $this->_phps = array();
        $this->_contents = preg_replace('/\s*\?>/', ' ?>', $this->_contents);
        $this->_contents = str_replace('<?=', '<?php echo ', $this->_contents);
        $this->_contents = preg_replace_callback('/<\?php.*?\?>/si', array($this, '_store_php'), $this->_contents);
    }

    protected function _store_php($matches)
    {
        $count = count($this->_phps);
        $this->_phps[] = $matches[0];
        return '@@@' . $this->_php_prefix . $count . '@@@';
    }

    protected function _restore_phps()
    {
        foreach ($this->_phps as $i => $php) {
            $this->_contents = str_replace('@@@' . $this->_php_prefix . $i . '@@@', $php, $this->_contents);
        }
    }

    protected function _process($matches)
    {
        $this->_code_raw = trim($matches[1]);
        if (in_array($this->_code_raw, $this->_exceptions)) {
            return $this->_l_delimiter . $this->_code_raw . $this->_r_delimiter;
        }
        $this->_preserve_strings();
        $this->_expand();
        $this->_trim();
        $this->_split();
        $this->_decode();
        $this->_unshift();
        $this->_push();
        if (end($this->_code_parsed_parts) != ':') {
            $this->_code_parsed_parts[] = ';';
        }
        $this->_code_parsed = implode(' ', $this->_code_parsed_parts);
        $this->_restore_strings();
        $this->_contract();
        $this->_code_parsed = trim($this->_code_parsed);
        return '<?php ' . $this->_code_parsed . ' ?>';
    }

    protected function _preserve_strings()
    {
        $this->_strings = array();
        $this->_code_raw = preg_replace_callback('/([\'\"])(.*?)\1/', array($this, '_store_string'), $this->_code_raw);
    }

    protected function _store_string($matches)
    {
        $count = count($this->_strings);
        $this->_strings[] = $matches[2];
        return $matches[1] . '@@@' . $this->_str_prefix . $count . '@@@' . $matches[1];
    }

    protected function _restore_strings()
    {
        foreach ($this->_strings as $i => $str) {
            $this->_code_parsed = str_replace('@@@' . $this->_str_prefix . $i . '@@@', $str, $this->_code_parsed);
        }
    }

    protected function _expand()
    {
        foreach ($this->_symbols as $symbol) {
            $this->_code_raw = str_replace($symbol, ' ' . $symbol . ' ', $this->_code_raw);
        }
    }

    protected function _trim()
    {
        $this->_code_raw = preg_replace('/\s+/', ' ', $this->_code_raw);
        $this->_code_raw = trim($this->_code_raw);
    }

    protected function _split()
    {
        $this->_code_raw_parts = explode(' ', $this->_code_raw);
    }

    protected function _contract()
    {
        $this->_code_parsed = str_replace('= >', '=>', $this->_code_parsed);
        $this->_code_parsed = str_replace('= =', '==', $this->_code_parsed);
        $this->_code_parsed = str_replace('= =', '==', $this->_code_parsed);
        $this->_code_parsed = str_replace('= =', '==', $this->_code_parsed);
        $this->_code_parsed = str_replace('> =', '>=', $this->_code_parsed);
        $this->_code_parsed = str_replace('< =', '<=', $this->_code_parsed);
        $this->_code_parsed = str_replace('! =', '!=', $this->_code_parsed);
    }

    protected function _decode()
    {
        $this->_to_unshift = array();
        $this->_to_push = array();
        $pos = 0;
        $this->_code_parsed_parts = array();
        foreach ($this->_code_raw_parts as $p) {
            if (in_array($p, $this->_reserved)) { // reserved code
                $this->_code_parsed_parts[] = $this->_decode_reserved($p, $pos);
            } else if (in_array($p, $this->_symbols)) { // symbol
                $this->_code_parsed_parts[] = $this->_decode_symbol($p, $pos);
            } else {
                $this->_code_parsed_parts[] = $this->_decode_string($p, $pos);
            }
            $pos++;
        }
    }

    protected function _unshift()
    {
        foreach ($this->_to_unshift as $unshift) {
            array_unshift($this->_code_parsed_parts, $unshift);
        }
    }

    protected function _push()
    {
        foreach ($this->_to_push as $push) {
            array_push($this->_code_parsed_parts, $push);
        }
    }

    protected function _decode_reserved($code, $position)
    {
        if ($code == '/' and $position == 0) {
            return '';
        }
        if ($code == 'if' || $code == 'foreach') {
            if ($this->_is_end()) {
                return 'end' . $code;
            }
            $this->_to_push[] = ')';
            $this->_to_push[] = ':';
            return $code . ' (';
        }
        if ($code == 'elif') {
            $this->_to_push[] = ')';
            $this->_to_push[] = ':';
            return 'elseif (';
        }
        if ($code == 'else') {
            $this->_to_push[] = ':';
            return $code;
        }
        return $code;
    }

    protected function _is_end()
    {
        return $this->_code_raw_parts[0] == '/';
    }

    protected function _decode_symbol($code, $position)
    {
        if ($code == '=') {
            if ($this->_code_raw_parts[0] == 'foreach') {
                return '=>';
            }
        }
        return $code;
    }

    protected function _decode_string($code, $position)
    {
        $code = preg_replace('/\[([^\'\"]*?)\]/', '[\'$1\']', $code);
        if ($position == 0) {
            $this->_to_unshift[] = 'echo';
        }
        foreach ($this->_exclude_patterns as $ex) {
            if (preg_match('/^' . $ex . '$/i', $code)) {
                return $code;
            }
        }
        if (preg_match('/^\w.*/', $code)) {
            $next = $position + 1;
            $prev = $position - 1;
            if ((isset($this->_code_raw_parts[$next]) and $this->_code_raw_parts[$next] == '(') or (isset($this->_code_raw_parts[$prev]) and $this->_code_raw_parts[$prev] == '->')) {
                return $code;
            }
            $code = '$' . $code;
        }
        $code = preg_replace('/^#(\w.*)/', '$1', $code);
        return $code;
    }

    protected function _set_delimiter_patterns()
    {
        $l_delimiter = preg_quote($this->_l_delimiter, '/');
        $this->_l_delimiter_pattern = '/' . $l_delimiter;
        $r_delimiter = preg_quote($this->_r_delimiter, '/');
        $this->_r_delimiter_pattern = $r_delimiter . '/';
    }

    public function set_delimiter($left_delimiter = '{', $right_delimiter = '}')
    {
        $this->_l_delimiter = $left_delimiter;
        $this->_r_delimiter = $right_delimiter;
        $this->_set_delimiter_patterns();
    }

    protected function _check_cache_dir()
    {
        if (!is_dir($this->_cache_dir)) {
            mkdir($this->_cache_dir);
        }
        if (!is_writable($this->_cache_dir)) {
            die('Cache directory is not writable.');
        }
        if (!is_dir($this->_cache_dir . 'views')) {
            mkdir($this->_cache_dir . 'views');
        }
    }

    public function add_exception($val)
    {
        if (is_array($val)) {
            $this->_exceptions = array_merge($this->_exceptions, $val);
        } else {
            array_push($this->_exceptions, $val);
        }
    }
}
