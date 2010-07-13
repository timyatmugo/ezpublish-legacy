<?php
/**
 * File containing (site)access functionality
 *
 * @copyright Copyright (C) 1999-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package kernel
 */

/**
 * Provides functions for siteaccess handling
 *
 * @package kernel
 */
class eZSiteAccess
{
    /**
     * Integer constants that identify the siteaccess matching used
     *
     * @since 4.4 Was earlier in access.php as normal constants
     */
    const TYPE_DEFAULT = 1;
    const TYPE_URI = 2;
    const TYPE_PORT = 3;
    const TYPE_HTTP_HOST = 4;
    const TYPE_INDEX_FILE = 5;
    const TYPE_STATIC = 6;
    const TYPE_SERVER_VAR = 7;
    const TYPE_URL = 8;
    const TYPE_HTTP_HOST_URI = 9;

    const SUBTYPE_PRE = 1;
    const SUBTYPE_POST = 2;

    /*!
     Constructor
    */
    function eZSiteAccess()
    {
    }

    static function siteAccessList()
    {
        $siteAccessList = array();
        $ini = eZINI::instance();
        $availableSiteAccessList = $ini->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' );
        if ( !is_array( $availableSiteAccessList ) )
            $availableSiteAccessList = array();

        $serverSiteAccess = eZSys::serverVariable( $ini->variable( 'SiteAccessSettings', 'ServerVariableName' ), true );
        if ( $serverSiteAccess )
            $availableSiteAccessList[] = $serverSiteAccess;

        $availableSiteAccessList = array_unique( $availableSiteAccessList );
        foreach ( $availableSiteAccessList as $siteAccessName )
        {
            $siteAccessItem = array();
            $siteAccessItem['name'] = $siteAccessName;
            $siteAccessItem['id'] = eZSys::ezcrc32( $siteAccessName );
            $siteAccessList[] = $siteAccessItem;
        }
        return $siteAccessList;
    }

    /**
     * Returns path to site access
     *
     * @param string $siteAccess
     * @return string|false Return path to siteacces or false if invalid
     */
    static function findPathToSiteAccess( $siteAccess )
    {
        $ini = eZINI::instance();
        $siteAccessList = $ini->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' );
        if ( !in_array( $siteAccess, $siteAccessList )  )
            return false;

        $currentPath = 'settings/siteaccess/' . $siteAccess;
        if ( file_exists( $currentPath ) )
            return $currentPath;

        $activeExtensions = eZExtension::activeExtensions();
        $baseDir = eZExtension::baseDirectory();
        foreach ( $activeExtensions as $extension )
        {
            $currentPath = $baseDir . '/' . $extension . '/settings/siteaccess/' . $siteAccess;
            if ( file_exists( $currentPath ) )
                return $currentPath;
        }

        return 'settings/siteaccess/' . $siteAccess;
    }

    /**
     * Re-initialises the current site access
     *
     * - clears all in-memory caches used by the INI system
     * - re-builds the list of paths where INI files are searched for
     * - re-searches module paths
     *
     * @return bool True if re-initialisation was successful
     */
    static function reInitialise()
    {
        if ( isset( $GLOBALS['eZCurrentAccess'] ) )
        {
            eZINI::resetAllGlobals();

            eZExtension::activateExtensions( 'default' );
            $accessName = $GLOBALS['eZCurrentAccess']['name'];
            if ( file_exists( "settings/siteaccess/$accessName" ) )
            {
                $ini = eZINI::instance();
                $ini->prependOverrideDir( "siteaccess/$accessName", false, 'siteaccess' );
            }
            eZExtension::prependExtensionSiteAccesses( $accessName );
            eZExtension::activateExtensions( 'access' );

            $moduleRepositories = eZModule::activeModuleRepositories();
            eZModule::setGlobalPathList( $moduleRepositories );

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Goes trough the access matching rules and returns the access match.
     * The returned match is an associative array with:
     *  name     => string Name of the siteaccess (same as folder name)
     *  type     => int The constant that represent the matching used
     *  uri_part => array(string) List of path elements that was used in start of url for the match
     *
     * @since 4.4
     * @param eZURI $uri
     * @param string $host
     * @param string(numeric) $port
     * @param string $file Example '/index.php'
     * @return array
     */
    public static function match( eZURI $uri, $host, $port, $file )
    {
        $ini = eZINI::instance();
        if ( $ini->hasVariable( 'SiteAccessSettings', 'StaticMatch' ) )
        {
            $match = $ini->variable( 'SiteAccessSettings', 'StaticMatch' );
            if ( $match != '' )
            {
                $access = array( 'name' => $match,
                                 'type' => eZSiteAccess::TYPE_STATIC,
                                 'uri_part' => array() );
                return $access;
            }
        }

        list( $siteAccessList, $order ) =
            $ini->variableMulti( 'SiteAccessSettings', array( 'AvailableSiteAccessList', 'MatchOrder' ) );
        $access = array( 'name' => $ini->variable( 'SiteSettings', 'DefaultAccess' ),
                         'type' => eZSiteAccess::TYPE_DEFAULT,
                         'uri_part' => array() );

        if ( $order == 'none' )
            return $access;

        $order = $ini->variableArray( 'SiteAccessSettings', 'MatchOrder' );

        // Change the default type to eZSiteAccess::TYPE_URI if we're using URI MatchOrder.
        // This is to keep backward compatiblity with the ezurl operator. ezurl has since
        // rev 4949 added default siteaccess to generated URLs, even when there is
        // no siteaccess in the current URL.
        if ( in_array( 'uri', $order ) )
        {
            $access['type'] = eZSiteAccess::TYPE_URI;
        }

        foreach ( $order as $matchprobe )
        {
            $name = '';
            $type = '';
            $match_type = '';
            $uri_part = array();

            switch( $matchprobe )
            {
                case 'servervar':
                {
                    if ( $serversiteaccess = eZSys::serverVariable( $ini->variable( 'SiteAccessSettings', 'ServerVariableName' ), true ) )
                    {
                        $access['name'] = $serversiteaccess;
                        $access['type'] = eZSiteAccess::TYPE_SERVER_VAR;
                        return $access;
                    }
                    else
                        continue;
                } break;
                case 'port':
                {
                    if ( $ini->hasVariable( 'PortAccessSettings', $port ) )
                    {
                        $access['name'] = $ini->variable( 'PortAccessSettings', $port );
                        $access['type'] = eZSiteAccess::TYPE_PORT;
                        return $access;
                    }
                    else
                        continue;
                } break;
                case 'uri':
                {
                    $type = eZSiteAccess::TYPE_URI;
                    $match_type = $ini->variable( 'SiteAccessSettings', 'URIMatchType' );

                    if ( $match_type == 'map' )
                    {
                        if ( $ini->hasVariable( 'SiteAccessSettings', 'URIMatchMapItems' ) )
                        {
                            $match_item = $uri->element( 0 );
                            $matchMapItems = $ini->variableArray( 'SiteAccessSettings', 'URIMatchMapItems' );
                            foreach ( $matchMapItems as $matchMapItem )
                            {
                                $matchMapURI = $matchMapItem[0];
                                $matchMapAccess = $matchMapItem[1];
                                if ( $access['name']  == $matchMapAccess and in_array( $matchMapAccess, $siteAccessList ) )
                                {
                                    $uri_part = array( $matchMapURI );
                                }
                                if ( $matchMapURI == $match_item and in_array( $matchMapAccess, $siteAccessList ) )
                                {
                                    $uri->increase( 1 );
                                    $uri->dropBase();
                                    $access['name'] = $matchMapAccess;
                                    $access['type'] = $type;
                                    $access['uri_part'] = array( $matchMapURI );
                                    return $access;
                                }
                            }
                        }
                    }
                    else if ( $match_type == 'element' )
                    {
                        $match_index = $ini->variable( 'SiteAccessSettings', 'URIMatchElement' );
                        $elements = $uri->elements( false );
                        $elements = array_slice( $elements, 0, $match_index );
                        $name = implode( '_', $elements );
                        $uri_part = $elements;
                    }
                    else if ( $match_type == 'text' )
                    {
                        $match_item = $uri->elements();
                        $matcher_pre = $ini->variable( 'SiteAccessSettings', 'URIMatchSubtextPre' );
                        $matcher_post = $ini->variable( 'SiteAccessSettings', 'URIMatchSubtextPost' );
                    }
                    else if ( $match_type == 'regexp' )
                    {
                        $match_item = $uri->elements();
                        $matcher = $ini->variable( 'SiteAccessSettings', 'URIMatchRegexp' );
                        $match_num = $ini->variable( 'SiteAccessSettings', 'URIMatchRegexpItem' );
                    }
                    else
                        continue;
                } break;
                case 'host':
                {
                    $type = eZSiteAccess::TYPE_HTTP_HOST;
                    $match_type = $ini->variable( 'SiteAccessSettings', 'HostMatchType' );
                    $match_item = $host;
                    if ( $match_type == 'map' )
                    {
                        if ( $ini->hasVariable( 'SiteAccessSettings', 'HostMatchMapItems' ) )
                        {
                            $matchMapItems = $ini->variableArray( 'SiteAccessSettings', 'HostMatchMapItems' );
                            foreach ( $matchMapItems as $matchMapItem )
                            {
                                $matchMapHost = $matchMapItem[0];
                                $matchMapAccess = $matchMapItem[1];
                                if ( $matchMapHost == $host )
                                {
                                    $access['name'] = $matchMapAccess;
                                    $access['type'] = $type;
                                    return $access;
                                }
                            }
                        }
                    }
                    else if ( $match_type == 'element' )
                    {
                        $match_index = $ini->variable( 'SiteAccessSettings', 'HostMatchElement' );
                        $match_arr = explode( '.', $match_item );
                        $name = $match_arr[$match_index];
                    }
                    else if ( $match_type == 'text' )
                    {
                        $matcher_pre = $ini->variable( 'SiteAccessSettings', 'HostMatchSubtextPre' );
                        $matcher_post = $ini->variable( 'SiteAccessSettings', 'HostMatchSubtextPost' );
                    }
                    else if ( $match_type == 'regexp' )
                    {
                        $matcher = $ini->variable( 'SiteAccessSettings', 'HostMatchRegexp' );
                        $match_num = $ini->variable( 'SiteAccessSettings', 'HostMatchRegexpItem' );
                    }
                    else
                        continue;
                } break;
                case 'host_uri':
                {
                    $type = eZSiteAccess::TYPE_HTTP_HOST_URI;
                    if ( $ini->hasVariable( 'SiteAccessSettings', 'HostUriMatchMapItems' ) )
                    {
                        $match_item = $uri->element( 0 );
                        if ( $match_item )
                        {
                            $matchMapItems = $ini->variableArray( 'SiteAccessSettings', 'HostUriMatchMapItems' );
                            $matchMethodDefault = $ini->variableArray( 'SiteAccessSettings', 'HostUriMatchMethodDefault' );

                            foreach ( $matchMapItems as $matchMapItem )
                            {
                                $matchHost = $matchMapItem[0];
                                $matchURI = $matchMapItem[1];
                                $matchAccess = $matchMapItem[2];

                                if ( $matchURI !== $match_item )
                                    continue;

                                switch( isset( $matchMapItem[3] ) ? $matchMapItem[3] : $matchMethodDefault )
                                {
                                    case 'strict':
                                    {
                                        $hasHostMatch = ( $matchHost === $host );
                                    } break;
                                    case 'start':
                                    {
                                        $hasHostMatch = ( strpos($host, $matchHost) === 0 );
                                    } break;
                                    case 'end':
                                    {
                                        $hasHostMatch = ( strstr($host, $matchHost) === $matchHost );
                                    } break;
                                    case 'part':
                                    {
                                        $hasHostMatch = ( strpos($host, $matchHost) !== false );
                                    } break;
                                    default:
                                    {
                                        $hasHostMatch = false;
                                        eZDebug::writeError( "Unknown host_uri host match: $matchMapItem[3]", "access" );
                                    } break;
                                }

                                if ( $hasHostMatch )
                                {
                                    $uri->increase( 1 );
                                    $uri->dropBase();
                                    $access['name'] = $matchAccess;
                                    $access['type'] = $type;
                                    $access['uri_part'] = array( $matchURI );
                                    return $access;
                                }
                            }
                        }
                    }
                } break;
                case 'index':
                {
                    $type = eZSiteAccess::TYPE_INDEX_FILE;
                    $match_type = $ini->variable( 'SiteAccessSettings', 'IndexMatchType' );
                    $match_item = $file;
                    if ( $match_type == 'element' )
                    {
                        $match_index = $ini->variable( 'SiteAccessSettings', 'IndexMatchElement' );
                        $match_pos = strpos( $match_item, '.php' );
                        if ( $match_pos !== false )
                        {
                            $match_item = substr( $match_item, 0, $match_pos );
                            $match_arr = explode( '_', $match_item );
                            $name = $match_arr[$match_index];
                        }
                    }
                    else if ( $match_type == 'text' )
                    {
                        $matcher_pre = $ini->variable( 'SiteAccessSettings', 'IndexMatchSubtextPre' );
                        $matcher_post = $ini->variable( 'SiteAccessSettings', 'IndexMatchSubtextPost' );
                    }
                    else if ( $match_type == 'regexp' )
                    {
                        $matcher = $ini->variable( 'SiteAccessSettings', 'IndexMatchRegexp' );
                        $match_num = $ini->variable( 'SiteAccessSettings', 'IndexMatchRegexpItem' );
                    }
                    else
                        continue;
                } break;
                default:
                {
                    eZDebug::writeError( "Unknown access match: $match", "access" );
                } break;
            }

            if ( $match_type == 'regexp' )
                $name = self::matchRegexp( $match_item, $matcher, $match_num );
            else if ( $match_type == 'text' )
                $name = self::matchText( $match_item, $matcher_pre, $matcher_post );

            if ( isset( $name ) && $name != '' )
            {
                $name = preg_replace( array( '/[^a-zA-Z0-9]+/', '/_+/', '/^_/', '/_$/' ),
                                      array( '_', '_', '', '' ),
                                      $name );

                if ( in_array( $name, $siteAccessList ) )
                {
                    if ( $type == eZSiteAccess::TYPE_URI )
                    {
                        if ( $match_type == 'element' )
                        {
                            $uri->increase( $match_index );
                            $uri->dropBase();
                        }
                        else if ( $match_type == 'regexp' )
                        {
                            $uri->setURIString( $match_item );
                        }
                        else if ( $match_type == 'text' )
                        {
                            $uri->setURIString( $match_item );
                        }
                    }
                    $access['type']     = $type;
                    $access['name']     = $name;
                    $access['uri_part'] = $uri_part;
                    return $access;
                }
            }
        }
        return $access;
    }

    /**
     * Match a regex expression
     *
     * @since 4.4
     * @param string $text
     * @param string $reg
     * @param int $num
     * @return string|null
     */
    static function matchRegexp( &$text, $reg, $num )
    {
        $reg = str_replace( '/', "\\/", $reg );
        if ( preg_match( "/$reg/", $text, $regs ) && $num < count( $regs ) )
        {
            $text = str_replace( $regs[$num], '', $text );
            return $regs[$num];
        }
        return null;
    }

    /**
     * Match a text string with pre or/or post text strings
     *
     * @since 4.4
     * @param string $text
     * @param string $match_pre
     * @param string $match_post
     * @return string|null
     */
    static function matchText( &$text, $match_pre, $match_post )
    {
        $ret = null;
        if ( $match_pre !== '' )
        {
            $pos = strpos( $text, $match_pre );
            if ( $pos === false )
                return null;

            $ret = substr( $text, $pos + strlen( $match_pre ) );
            $text = substr( $text, 0, $pos );
        }
        if ( $match_post !== '' )
        {
            $pos = strpos( $ret, $match_post );
            if ( $pos === false )
                return null;

            $text .= substr( $ret, $pos + 1 );
            $ret = substr( $ret, 0, $pos );
        }
        return $ret;
    }

   /**
    * Changes the site access to what's defined in $access. It will change the
    * access path in eZSys and prepend an override dir to eZINI
    *
    * @since 4.4
    * @param array $access An associative array with 'name' (string), 'type' (int) and 'uri_part' (array).
    * @return array The $access parameter
    */
    static function change( array $access )
    {
        eZSys::clearAccessPath();
        $GLOBALS['eZCurrentAccess'] =& $access;

        $name = $access['name'];
        if ( isset( $access['uri_part'] ) &&
             $access['uri_part'] !== null )
        {
            eZSys::setAccessPath( $access['uri_part'], $name );
        }

        $ini = eZINI::instance();
        if ( file_exists( "settings/siteaccess/$name" ) )
        {
            $ini->prependOverrideDir( "siteaccess/$name", false, 'siteaccess' );
        }

        /* Make sure extension siteaccesses are prepended */
        eZExtension::prependExtensionSiteAccesses( $name );

        $ini->loadCache();

        eZUpdateDebugSettings();
        if ( self::debugEnabled() )
        {
            eZDebug::writeDebug( "Updated settings to use siteaccess '$name'", __METHOD__ );
        }

        return $access;
    }

    /**
     * Get current siteaccess data
     *
     * @since 4.4
     * return array|null
     */
    static function current()
    {
        if ( isset( $GLOBALS['eZCurrentAccess']['name'] ) )
            return $GLOBALS['eZCurrentAccess'];
        return null;
    }

    /**
     * Checks if site access debug is enabled
     *
     * @since 4.4
     * @return bool
     */
    static function debugEnabled()
    {
        $ini = eZINI::instance();
        return $ini->variable( 'SiteAccessSettings', 'DebugAccess' ) === 'enabled';
    }

    /**
     * Checks if extra site access debug is enabled
     *
     * @since 4.4
     * @return bool
     */
    static function extraDebugEnabled()
    {
        $ini = eZINI::instance();
        return $ini->variable( 'SiteAccessSettings', 'DebugExtraAccess' ) === 'enabled';
    }
}

?>
