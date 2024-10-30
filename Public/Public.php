<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    mFloorMap
 */
class mFloorMap_Public_Public {


    // singleton instance
    protected static $Instance;

    // instance of main plugin class
    /* @var mFloorMap $Plugin */
    protected $Plugin;

    // instance of shortcode class
    protected $Shortcode;

    // shortcode's rendering "id" attr
    protected $ShortcodeId;



    /**
     * Constructor.
     */
    public function __construct() {

        $this->Plugin= mFloorMap::GetInstance();
    }


    /**
     * Return singleton instance.
     *
     * @return self
     */
    public static function GetInstance() {

        if (!self::$Instance) {
            self::$Instance= new self;
        }
        return self::$Instance;
    }


    public function Run() {

        // admin page hooks
        add_action('get_header', array($this, 'OnWpHead'));

        // allow customization of title tag from inside of shortcode
        add_filter('pre_get_document_title', array($this, 'OnTitleTag'));

        // register shortcodes
        add_shortcode('mfloormap-facility', array($this, 'ShortCodeProcessor'));
        add_shortcode('mfloormap-floor', array($this, 'ShortCodeProcessor'));
    }


    /**
     * Hook listener for "wp_head" action.
     */
    public function OnWpHead() {

        // prepare our shortcode object
        $this->LoadShortcode();
    }


    /**
     * Hook listener for "pre_get_document_title" filter.
     * Must return string to implicitly set title
     * or null to delegate it to WP.
     *
     * @return string
     */
    public function OnTitleTag() {

        return is_object($this->Shortcode) && method_exists($this->Shortcode, 'GetPlaceTitleTag')
            // if our shortcode is "place" then overwrite title with place title
            ? $this->Shortcode->GetPlaceTitleTag($this->ShortcodeId)
            // otherwise left it to WP to generate title
            : null;
    }



    /**
     * Callable for all short-codes registered by "add_shortcode()".
     *
     * @param array $Attrs  custom parameters from short-code tag
     * @param string $Content  partial enclosed between opening and closing short-code tags
     * @param string $Tag  short-code itself
     * @return string  rendered HTML
     */
    public function ShortCodeProcessor($Attrs, $Content, $Tag) {

        // shortcode object is already prepared in 'pre_get_document_title' filter
        $Content= is_object($this->Shortcode)
            ? $this->Shortcode->Render($this->ShortcodeId)
            : '';
        return $Content;
    }


    /**
     * Analyze which shortcode to load and instantiate it.
     */
    protected function LoadShortcode() {

        // enumerate all short-tags
        $ShortTags= array('mfloormap-facility', 'mfloormap-floor');

        // fetch content of post
        global $wp_query;
        if (!$wp_query->post) {
            return;
        }
        $Page= get_page($wp_query->post->ID);
        $Content= $Page->post_content;

        // search for any shortcode
        // because of performances pack it in single pattern and perform only one regex search
        $Reg= array();
        foreach ($ShortTags as $Tag) {
            $Reg[]= '(('.preg_quote($Tag,'~').')\s+id=[\'"](\d+)[\'"])';
        }
        // prefixing with "?|" to force aligning all results in same keys
        preg_match('~(?|'.implode('|',$Reg).')~', $Content, $Matches);
        if (empty($Matches)) {
            return;
        }

        // shortcode found, load shortcode class
        $ShortcodeName= $Matches[2];
        $ShortcodeId= intval($Matches[3]);

        // is it request for place?
        $PlaceId= isset($_GET['mFloorMapPlace'])
            ? substr($_GET['mFloorMapPlace'], strrpos($_GET['mFloorMapPlace'], '-')+1)
            : 0;
        if ($PlaceId) {
            $this->Shortcode= new mFloorMap_Public_Shortcode_Place_Place;
            $this->ShortcodeId= $PlaceId;
            return;
        }

        $this->Shortcode= $ShortcodeName === 'mfloormap-floor'
            ? new mFloorMap_Public_Shortcode_Floor_Floor
            : new mFloorMap_Public_Shortcode_Facility_Facility;
        $this->ShortcodeId= $ShortcodeId;
    }


    /**
     * Returns full URL of current page.
     *
     * @return string
     */
    public function GetCurrentPageURL() {

        global $wp;
        return home_url(add_query_arg(array(), $wp->request));
    }


    /**
     * Return colors to be used in frontend.
     * They can be setted in "options" table or via filter "pre_option_{$option}"
     *
     * @return array
     */
    public static function GetColors() {

        $Options= mFloorMap::GetInstance()->GetOptions();
        return array(
            'HighlightStroke'=> isset($Options['ColorHS']) ? $Options['ColorHS'] : '000000',
            'HighlightFill'=> isset($Options['ColorHF']) ? $Options['ColorHF'] : 'cc0022',
            'SelectStroke'=> isset($Options['ColorSS']) ? $Options['ColorSS'] : 'aa2200',
            'SelectFill'=> isset($Options['ColorSF']) ? $Options['ColorSF'] : 'cc4400',
        );
    }


    /**
     * Perform transliteration of supplied text to make it URL-friendly and SEO-friendly.
     *
     * @param string $Text
     * @return string
     */
    public static function Transliterate($Text) {

        $Codes= array(
            // Default translations, can be overriden later
            'À'=>'A',   'Á'=>'A',   'Â'=>'A',   'Ã'=>'A',   'Ä'=>'A',   'Å'=>'A',   'Æ'=>'A',   'А'=>'A',
            'à'=>'a',   'á'=>'a',   'â'=>'a',   'ã'=>'a',   'ä'=>'a',   'å'=>'a',   'æ'=>'a',   'а'=>'a',   'ა'=>'a',
            'Б'=>'B',
            'б'=>'b',   'ბ'=>'b',
            'Ç'=>'C',   'Ц'=>'C',   'Č'=>'C',   'Ć'=>'C',   'ÄŒ'=>'C',  'Ä†'=>'C',  'Ч'=>'C',
            'ç'=>'c',   'ц'=>'c',   'č'=>'c',   'ć'=>'c',   'Ä‡'=>'c',  'Ä'=>'c',  'ч'=>'c',
            'Д'=>'D',
            'д'=>'d',   'დ'=>'d',
            'È'=>'E',   'É'=>'E',   'Ê'=>'E',   'Ë'=>'E',   'Е'=>'E',   'Ё'=>'Jo',
            'è'=>'e',   'é'=>'e',   'ê'=>'e',   'ë'=>'e',   'е'=>'e',   'ё'=>'jo',   'ე'=>'e',
            'Ф'=>'F',
            'ф'=>'f',
            'Г'=>'G',   'Ґ'=>'G',
            'г'=>'g',   'ґ'=>'g',   'გ'=>'g',
            'Х'=>'H',
            'х'=>'h',   'ჰ'=>'h',
            'Ì'=>'I',   'Í'=>'I',   'Î'=>'I',   'Ï'=>'I',   'И'=>'I',   'І'=>'I',   'Ї'=>'I',   'Є'=>'Ie',
            'ì'=>'i',   'í'=>'i',   'î'=>'i',   'ï'=>'i',   'и'=>'i',   'і'=>'i',   'ї'=>'i',   'є'=>'ie',   'ი'=>'i',
            'Й'=>'J',   'Э'=>'Je',  'Ю'=>'Ju',  'Я'=>'Ja',
            'й'=>'j',   'э'=>'je',  'ю'=>'ju',  'я'=>'ja',  'ჯ'=>'j',
            'К'=>'K',
            'к'=>'k',   'კ'=>'k',
            'Л'=>'L',
            'л'=>'l',   'ლ'=>'l',
            'М'=>'M',
            'м'=>'m',   'მ'=>'m',
            'Ñ'=>'N',   'Н'=>'N',
            'ñ'=>'n',   'н'=>'n',   'ნ'=>'n',
            'Ò'=>'O',   'Ó'=>'O',   'Ô'=>'O',   'Õ'=>'O',   'Ö'=>'O',   'Ø'=>'O',   'Œ'=>'O',   'О'=>'O',
            'ò'=>'o',   'ó'=>'o',   'ô'=>'o',   'õ'=>'o',   'ö'=>'o',   'ø'=>'o',   'œ'=>'o',   'о'=>'o',   'ð'=>'o',   'ო'=>'o',
            'П'=>'P',
            'п'=>'p',   'პ'=>'p',
            'Р'=>'R',
            'р'=>'r',   'რ'=>'r',
            'Š'=>'S',   'š'=>'s',   'С'=>'S',   'Ш'=>'S',   'Щ'=>'S',   'Å '=>'S',
            'Å¡'=>'s',  'ß'=>'s',   'с'=>'s',   'ш'=>'s',   'щ'=>'s',   'ს'=>'s',
            'Т'=>'T',
            'т'=>'t',   'ტ'=>'t',
            'Ù'=>'U',   'Ú'=>'U',   'Û'=>'U',   'Ü'=>'U',   'У' => 'U',
            'ù'=>'u',   'ú'=>'u',   'û'=>'u',   'ü'=>'u',   'у' => 'u',   'µ'=>'u',   'უ'=>'u',
            'В'=>'V',
            'в'=>'v',   'ვ'=>'v',
            'Ÿ'=>'Y',   'Ý'=>'Y',   'Ы' => 'Y',   '¥'=>'Y',
            'ÿ'=>'y',   'ý'=>'y',   'ы' => 'y',
            'Ž'=>'Z',   'З'=>'Z',   'Å½'=>'Z',   'Ж'=>'Z',
            'ž'=>'z',   'з'=>'z',   'Å¾'=>'z',   'ж'=>'z',   'ზ'=>'z',
            'Љ'=>'Lj',  'љ'=>'lj',  'Њ'=>'Nj',   'њ'=>'nj',  'Џ'=>'Dz',  'џ'=>'dz',
            "Đ"=>"Dj",  "Ð"=>"Dj",  "đ"=>"dj",   'Ä?'=>'Dj', 'Ä‘'=>'dj',
            'თ'=>'th',  'ჟ'=>'zh',  'ფ'=>'ph',   'ქ'=>'q',   'ღ'=>'gh',  'ყ'=>'qh',  'შ'=>'sh',
            'ჩ'=>'ch',  'ც'=>'ts',  'ძ'=>'dz',   'წ'=>'ts',  'ჭ'=>'tch', 'ხ'=>'kh',
	  );
        $Separators= array(
            ' '=>'-',   '+'=>'-',   '"'=>'-',   "'"=>'-',   '&'=>'-',
            ':'=>'-',   ','=>'-',   '*'=>'-',   '/'=>'-',
        );
        // replace chars
	$Transliterated= strtr($Text, $Codes+$Separators);
        // remove all invalid chars
        $Clean= preg_replace("/[^A-Za-z0-9'_\-\.]/", '-', $Transliterated);
        // compress multiple separators into single
        $Compressed= preg_replace('/\-+/', '-', $Clean);
        // remove outer separators and return
        return trim($Compressed, '-');
    }
}

?>