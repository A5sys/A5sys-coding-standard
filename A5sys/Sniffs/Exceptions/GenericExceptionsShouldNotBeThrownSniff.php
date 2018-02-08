<?php
/**
 * This file is part of the A5sys-coding-standard (phpcs standard)
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer-Symfony2
 * @author   Symfony2-phpcs-authors <Symfony2-coding-standard@escapestudios.github.com>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @version  GIT: master
 * @link     https://github.com/escapestudios/Symfony2-coding-standard
 */

use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * A5sys_Sniffs_Exceptions_GenericExceptionsShouldNotBeThrownSniff.
 *
 * If you throw a general exception type, such as ErrorException, RuntimeException, or Exception in a library or framework, it forces consumers to catch all exceptions, including unknown exceptions that they do not know how to handle.
 *
 * Instead, either throw a subtype that already exists in the Standard PHP Library, or create your own type that derives from Exception.
 *
 * @category PHP
 * @package  PHP_CodeSniffer-Symfony2
 * @author   Dave Hauenstein <davehauenstein@gmail.com>
 * @author   wicliff wolda <dev@bloody-wicked.com>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @link     https://github.com/escapestudios/Symfony2-coding-standard
 */
class A5sys_Sniffs_Exceptions_GenericExceptionsShouldNotBeThrownSniff implements Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array('PHP');

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_THROW);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr)
    {
        $tokens   = $phpcsFile->getTokens();
        $line     = $tokens[$stackPtr]['line'];
        $exceptionClass = $phpcsFile->findNext(T_STRING, $stackPtr);

        if (in_array($tokens[$exceptionClass]['content'], array('ErrorException', 'RuntimeException', 'Exception'))) {
            $phpcsFile->addError(
                'Generic exceptions ErrorException, RuntimeException and Exception should not be thrown',
                $stackPtr,
                'GenericExceptionsShouldNotBeThrown'
            );
        }

        return;
    }
}
