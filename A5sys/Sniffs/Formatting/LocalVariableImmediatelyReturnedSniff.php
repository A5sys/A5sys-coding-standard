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
 * Throws warnings if the self is used instead of static
 *
 * @category PHP
 * @package  PHP_CodeSniffer-Symfony2
 * @author   Thomas BEAUJEAN
 * @license  http://spdx.org/licenses/MIT MIT License
 * @link     https://github.com/escapestudios/Symfony2-coding-standard
 */
class A5sys_Sniffs_Formatting_LocalVariableImmediatelyReturnedSniff implements PHP_CodeSniffer_Sniff
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
        return array(T_RETURN, T_THROW);
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

        //if the return is a variable
        $functionEnd = false;
        $current = $stackPtr + 1;
        $returnedName = null;
        while ($functionEnd === false) {
            if ($tokens[$current]['type'] === 'T_SEMICOLON') {
                $functionEnd = true;
            } elseif ($tokens[$current]['type'] === 'T_VARIABLE') {
                if ('$this' !== $tokens[$current]['content']) {
                    $returnedName = $tokens[$current]['content'];
                } else {
                    //there is no need to continue if the $this is encountered
                    $functionEnd = true;
                }
            } elseif ($tokens[$current]['type'] !== 'T_WHITESPACE') {
                //there is something else than the semicolon after the variable
                //so it is a false positive
                $returnedName = null;
                $functionEnd = true;
            }
            $current++;
        }

        //does the function contains more than once an assignation for this variable
        $count = 0;
        if ($returnedName !== null) {
            $current = $stackPtr;

            while ($tokens[$current]['type'] !== 'T_FUNCTION') {
                if ($tokens[$current]['type'] === 'T_VARIABLE'  && $returnedName === $tokens[$current]['content']) {
                    $count++;
                }
                $current--;
            }
        }

        //
        $inPreviousLine = false;
        if ($returnedName !== null) {
            $current = $stackPtr;
            $semicolonFound = 0;
            //the first semicolon is the end of the previous instruction
            //so we look only into the the two previous semicolons
            while ($tokens[$current]['type'] !== 'T_FUNCTION' && $semicolonFound < 2) {
                if ($tokens[$current]['type'] === 'T_SEMICOLON') {
                    $semicolonFound++;
                }
                if ($tokens[$current]['type'] === 'T_VARIABLE'  && $returnedName === $tokens[$current]['content']) {
                    $inPreviousLine = true;
                }
                $current--;
            }
        }

        //so raise error
        if ($returnedName !== null && $count === 1 && $inPreviousLine === true) {
            $phpcsFile->addError(
                'Local variables should not be declared and then immediately returned or thrown',
                $stackPtr
            );
        }

        return;
    }//end process()
}//end class
