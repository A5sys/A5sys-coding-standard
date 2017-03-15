<?php
/**
 * This file is part of the A5sys-coding-standard (phpcs standard)
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer-Symfony2
 * @author   Thomas BEAUJEAN
 * @license  http://spdx.org/licenses/MIT MIT License
 * @version  GIT: master
 * @link     https://github.com/escapestudios/Symfony2-coding-standard
 */

/**
 * A5sys_Sniffs_Constant_SelfSniff.
 *
 * Throws error if the self is used instead of static,
 * unless the self is into the method signature (static causes compilation error in signature)
 *
 * @category PHP
 * @package  PHP_CodeSniffer-Symfony2
 * @author   Thomas BEAUJEAN
 * @license  http://spdx.org/licenses/MIT MIT License
 * @link     https://github.com/escapestudios/Symfony2-coding-standard
 */
class A5sys_Sniffs_Constant_SelfSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'PHP',
                                  );

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_SELF);
    }//end register()

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisPtr = null;
        $closeParenthesisPtr = null;
        $openParenthesisOwner = null;
        if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
            $openParenthesisPtr = array_keys($tokens[$stackPtr]['nested_parenthesis'])[0];
            $openParenthesisToken = $tokens[array_keys($tokens[$stackPtr]['nested_parenthesis'])[0]];
            $closeParenthesisPtr = $openParenthesisToken['parenthesis_closer'];

            if (isset($openParenthesisToken['parenthesis_owner'])) {
                $openParenthesisOwner = $tokens[$openParenthesisToken['parenthesis_owner']];
            }
        }

        $addWarning = true;
        // if self is into the method signature, don't add the error
        if ($openParenthesisPtr
                && $closeParenthesisPtr
                // between "(" and ")"
                && $openParenthesisPtr < $stackPtr && $closeParenthesisPtr > $stackPtr
                // owner of "(" is a T_FUNCTION
                && $openParenthesisOwner['code'] === T_FUNCTION) {
            $addWarning = false;
        }

        if ($addWarning) {
            $phpcsFile->addWarning(
                'Please use STATIC instead of SELF',
                $stackPtr,
                'Invalid'
            );
        }
    }//end process()
}//end class
