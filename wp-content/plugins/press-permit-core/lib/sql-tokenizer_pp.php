<?php
class SqlTokenizer_WP extends SqlTokenizer {
	public function __construct() {
		$this->separators = array_merge( $this->separators, array( 'post_status', 'post_author', 'post_date', 'post_date_gmt', 'post_parent', 'post_modified', 'post_modified_gmt', 'ID', 'post_name', 'guid' ) );
	}
}

/**
 * This is a simple sql tokenizer.
 *
 * It does NOT support multiline comments at this time. Derived from SqlParser
 * - http://www.tehuber.com/article.php?story=20081016164856267
 * Modified by Kevin Behrens to add ParseArg method, remove other methods and properties
 *
 * THIS CODE IS A PROTOTYPE/BETA
 *
 * @author Justin Carlson <justin.carlson@gmail.com>
 * @modified Kevin Behrens <kevin@agapetry.net>
 * @license LGPL 3
 * @version 0.0.4
 */
class SqlTokenizer {
    var $querysections = array( 'alter', 'create', 'drop', 'select', 'delete', 'insert','update', 'from', 'where', 'limit', 'order' );
    var $operators = array( '=', '<>', '<', '<=', '>', '>=', 'like', 'clike', 'slike', 'not', 'is', 'in', 'between' );
    var $separators = array( 'and' );
    var $startparens = array( '{', '(' );
    var $endparens = array( '}', ')' );
    var $tokens = array( ',', ' ' );

	/**
     * Simple SQL Tokenizer
     *
     * @author Kevin Behrens <kevin@agapetry.net>
     * @param string $sqlQuery
	 * @param string $arg_name
     * @return token array
	 * @description ParseArg() is used by Press Permit to extract the post_type argument from WordPress SQL queries.  It is not tested for other usage.
     */
	function ParseArg( $sqlQuery, $arg_name ) {
		$tokens = $this->Tokenize( strtolower($sqlQuery) );
		$return = array();
		
		if ( $array_pos = array_search( $arg_name, $tokens ) ) {
			$ilim = count($tokens);
			for( $i=$array_pos+1; $i<$ilim; $i++ ) {
				if ( in_array( $tokens[$i], $this->endparens ) || in_array( $tokens[$i], $this->separators ) )
					return $return;
			
				if ( ! in_array( $tokens[$i], $this->tokens ) && ! in_array( $tokens[$i], $this->startparens ) && ! in_array( $tokens[$i], $this->operators ) && ! in_array( $tokens[$i], $this->querysections ) ) {
					$return []= str_replace( "'", "", $tokens[$i] );
				}
			}
		}
		
		return $return;
	}
	
    /**
     * function Tokenize
     *
	 * @author Justin Carlson <justin.carlson@gmail.com>
     * @param string $sqlQuery
     * @return token array
     */
    public static function Tokenize($sqlQuery, $cleanWhitespace = true) {

        /**
         * Strip extra whitespace from the query
         */
        if ($cleanWhitespace === true) {
            $sqlQuery = ltrim(preg_replace('/[\\s]{2,}/', ' ', $sqlQuery));
        }

        /**
         * Regular expression parsing.
         * Inspired/Based on the Perl SQL::Tokenizer by Igor Sutton Lopes
         */
       
        // begin group
        $regex = '(';
       
        // inline comments
        $regex .= '(?:--|\\#)[\\ \\t\\S]*';
       
        // logical operators
        $regex .= '|(?:<>|<=>|>=|<=|==|=|!=|!|<<|>>|<|>|\\|\\||\\||&&|&|-';
        $regex .= '|\\+|\\*(?!\/)|\/(?!\\*)|\\%|~|\\^|\\?)';
       
        // empty quotes
        $regex .= '|[\\[\\]\\(\\),;`]|\\\'\\\'(?!\\\')|\\"\\"(?!\\"")';
       
        // string quotes
        $regex .= '|".*?(?:(?:""){1,}"';
        $regex .= '|(?<!["\\\\])"(?!")|\\\\"{2})';
        $regex .= '|\'.*?(?:(?:\'\'){1,}\'';
        $regex .= '|(?<![\'\\\\])\'(?!\')';
        $regex .= '|\\\\\'{2})';
       
        // c comments
        $regex .= '|\/\\*[\\ \\t\\n\\S]*?\\*\/';
       
        // wordds, column strings, params
        $regex .= '|(?:[\\w:@]+(?:\\.(?:\\w+|\\*)?)*)';
        $regex .= '|[\t\ ]+';
       
        // period and whitespace
        $regex .= '|[\.]';
        $regex .= '|[\s]';

        $regex .= ')'; # end group
       
        // perform a global match
        preg_match_all('/' . $regex . '/smx', $sqlQuery, $result);

        // return tokens
        return $result[0];
    }
}
