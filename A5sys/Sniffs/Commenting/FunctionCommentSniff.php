<?php
/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
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

use PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FunctionCommentSniff as PEARFunctionCommentSniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Util\Common;

/**
 * Symfony2 standard customization to PEARs FunctionCommentSniff.
 *
 * Verifies that :
 * <ul>
 *  <li>There is a phpdoc comment on each function. Applies to public, protected and private methods.</li>
 * </ul>
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Felix Brandt <mail@felixbrandt.de>
 * @license  http://spdx.org/licenses/BSD-3-Clause BSD 3-clause "New" or "Revised" License
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class A5sys_Sniffs_Commenting_FunctionCommentSniff extends PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FunctionCommentSniff
{
    /**
     * The current PHP version.
     *
     * @var integer
     */
    private $phpVersion = null;

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr)
    {
        if (false === $commentEnd = $phpcsFile->findPrevious(array(T_COMMENT, T_DOC_COMMENT, T_CLASS, T_FUNCTION, T_OPEN_TAG), ($stackPtr - 1))) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$commentEnd]['code'];

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Process the return comment of this function comment.
     *
     * @param PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the current token
     *                                           in the stack passed in $tokens.
     * @param int                  $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processReturn(PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr, $commentStart)
    {

        if ($this->isInheritDoc($phpcsFile, $stackPtr)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // Only check for a return comment if a non-void return statement exists
        if (isset($tokens[$stackPtr]['scope_opener'])) {
            // Start inside the function
            $start = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPtr, $tokens[$stackPtr]['scope_closer']);
            for ($i = $start; $i < $tokens[$stackPtr]['scope_closer']; ++$i) {
                // Skip closures
                if ($tokens[$i]['code'] === T_CLOSURE) {
                    $i = $tokens[$i]['scope_closer'];
                    continue;
                }
                // Found a return not in a closure statement
                // Run the check on the first which is not only 'return;'
                if ($tokens[$i]['code'] === T_RETURN && $this->isMatchingReturn($tokens, $i)) {
                    parent::processReturn($phpcsFile, $stackPtr, $commentStart);
                    break;
                }
            }
        }

    } /* end processReturn() */


    /**
     * Process the function parameter comments.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the current token
     *                                                  in the stack passed in $tokens.
     * @param int                         $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processParams(File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        if ($this->isInheritDoc($phpcsFile, $stackPtr)) {
            return;
        }

        if ($this->phpVersion === null) {
            $this->phpVersion = Config::getConfigData('php_version');
            if ($this->phpVersion === null) {
                $this->phpVersion = PHP_VERSION_ID;
            }
        }

        $tokens = $phpcsFile->getTokens();

        $params  = [];
        $maxType = 0;
        $maxVar  = 0;
        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] !== '@param') {
                continue;
            }

            $type         = '';
            $typeSpace    = 0;
            $var          = '';
            $varSpace     = 0;
            $comment      = '';
            $commentLines = [];
            if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
                $matches = [];
                preg_match('/([^$&.]+)(?:((?:\.\.\.)?(?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/', $tokens[($tag + 2)]['content'], $matches);

                if (empty($matches) === false) {
                    $typeLen   = strlen($matches[1]);
                    $type      = trim($matches[1]);
                    $typeSpace = ($typeLen - strlen($type));
                    $typeLen   = strlen($type);
                    if ($typeLen > $maxType) {
                        $maxType = $typeLen;
                    }
                }

                if (isset($matches[2]) === true) {
                    $var    = $matches[2];
                    $varLen = strlen($var);
                    if ($varLen > $maxVar) {
                        $maxVar = $varLen;
                    }

                    if (isset($matches[4]) === true) {
                        $varSpace       = strlen($matches[3]);
                        $comment        = $matches[4];
                        $commentLines[] = [
                            'comment' => $comment,
                            'token'   => ($tag + 2),
                            'indent'  => $varSpace,
                        ];

                        // Any strings until the next tag belong to this comment.
                        if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                            $end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
                        } else {
                            $end = $tokens[$commentStart]['comment_closer'];
                        }

                        for ($i = ($tag + 3); $i < $end; $i++) {
                            if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                                $indent = 0;
                                if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                                    $indent = strlen($tokens[($i - 1)]['content']);
                                }

                                $comment       .= ' '.$tokens[$i]['content'];
                                $commentLines[] = [
                                    'comment' => $tokens[$i]['content'],
                                    'token'   => $i,
                                    'indent'  => $indent,
                                ];
                            }
                        }
                    } else {
                        /*$error = 'Missing parameter comment';
                        $phpcsFile->addError($error, $tag, 'MissingParamComment');
                        $commentLines[] = ['comment' => ''];*/
                    }//end if
                } else {
                    $error = 'Missing parameter name';
                    $phpcsFile->addError($error, $tag, 'MissingParamName');
                }//end if
            } else {
                $error = 'Missing parameter type';
                $phpcsFile->addError($error, $tag, 'MissingParamType');
            }//end if

            $params[] = [
                'tag'          => $tag,
                'type'         => $type,
                'var'          => $var,
                'comment'      => $comment,
                'commentLines' => $commentLines,
                'type_space'   => $typeSpace,
                'var_space'    => $varSpace,
            ];
        }//end foreach

        $realParams  = $phpcsFile->getMethodParameters($stackPtr);
        $foundParams = [];

        // We want to use ... for all variable length arguments, so added
        // this prefix to the variable name so comparisons are easier.
        foreach ($realParams as $pos => $param) {
            if ($param['variable_length'] === true) {
                $realParams[$pos]['name'] = '...'.$realParams[$pos]['name'];
            }
        }

        foreach ($params as $pos => $param) {
            // If the type is empty, the whole line is empty.
            if ($param['type'] === '') {
                continue;
            }

            // Check the param type value.
            $typeNames          = explode('|', $param['type']);
            $suggestedTypeNames = [];

            foreach ($typeNames as $typeName) {
                // Strip nullable operator.
                if ($typeName[0] === '?') {
                    $typeName = substr($typeName, 1);
                }

                $suggestedName        = Common::suggestType($typeName);
                $suggestedTypeNames[] = $suggestedName;

                if (count($typeNames) > 1) {
                    continue;
                }

                // Check type hint for array and custom type.
                $suggestedTypeHint = '';
                if (strpos($suggestedName, 'array') !== false || substr($suggestedName, -2) === '[]') {
                    $suggestedTypeHint = 'array';
                } else if (strpos($suggestedName, 'callable') !== false) {
                    $suggestedTypeHint = 'callable';
                } else if (strpos($suggestedName, 'callback') !== false) {
                    $suggestedTypeHint = 'callable';
                } else if (in_array($suggestedName, Common::$allowedTypes) === false) {
                    $suggestedTypeHint = $suggestedName;
                }

                if ($this->phpVersion >= 70000) {
                    if ($suggestedName === 'string') {
                        $suggestedTypeHint = 'string';
                    } else if ($suggestedName === 'int' || $suggestedName === 'integer') {
                        $suggestedTypeHint = 'int';
                    } else if ($suggestedName === 'float') {
                        $suggestedTypeHint = 'float';
                    } else if ($suggestedName === 'bool' || $suggestedName === 'boolean') {
                        $suggestedTypeHint = 'bool';
                    }
                }

                if ($this->phpVersion >= 70200) {
                    if ($suggestedName === 'object') {
                        $suggestedTypeHint = 'object';
                    }
                }

                if ($suggestedTypeHint !== '' && isset($realParams[$pos]) === true) {
                    $typeHint = $realParams[$pos]['type_hint'];

                    // Remove namespace prefixes.
                    $suggestedTypeHint = substr($suggestedTypeHint, (strlen($typeHint) * -1));

                    if ($typeHint === '') {
                        $error = 'Type hint "%s" missing for %s';
                        $data  = [
                            $suggestedTypeHint,
                            $param['var'],
                        ];

                        $errorCode = 'TypeHintMissing';
                        if ($suggestedTypeHint === 'string'
                            || $suggestedTypeHint === 'int'
                            || $suggestedTypeHint === 'float'
                            || $suggestedTypeHint === 'bool'
                        ) {
                            $errorCode = 'Scalar'.$errorCode;
                        }

                        $phpcsFile->addError($error, $stackPtr, $errorCode, $data);
                    } else if ($typeHint !== $suggestedTypeHint && $typeHint !== '?'.$suggestedTypeHint) {
                        $error = 'Expected type hint "%s"; found "%s" for %s';
                        $data  = [
                            $suggestedTypeHint,
                            $typeHint,
                            $param['var'],
                        ];
                        $phpcsFile->addError($error, $stackPtr, 'IncorrectTypeHint', $data);
                    }//end if
                } else if ($suggestedTypeHint === '' && isset($realParams[$pos]) === true) {
                    $typeHint = $realParams[$pos]['type_hint'];
                    if ($typeHint !== '') {
                        $error = 'Unknown type hint "%s" found for %s';
                        $data  = [
                            $typeHint,
                            $param['var'],
                        ];
                        $phpcsFile->addError($error, $stackPtr, 'InvalidTypeHint', $data);
                    }
                }//end if
            }//end foreach

            if ($param['var'] === '') {
                continue;
            }

            $foundParams[] = $param['var'];

            // Check number of spaces after the type.
            $this->checkSpacingAfterParamType($phpcsFile, $param, $maxType);

            // Make sure the param name is correct.
            if (isset($realParams[$pos]) === true) {
                $realName = $realParams[$pos]['name'];
                if ($realName !== $param['var']) {
                    $code = 'ParamNameNoMatch';
                    $data = [
                        $param['var'],
                        $realName,
                    ];

                    $error = 'Doc comment for parameter %s does not match ';
                    if (strtolower($param['var']) === strtolower($realName)) {
                        $error .= 'case of ';
                        $code   = 'ParamNameNoCaseMatch';
                    }

                    $error .= 'actual variable name %s';

                    $phpcsFile->addError($error, $param['tag'], $code, $data);
                }
            } else if (substr($param['var'], -4) !== ',...') {
                // We must have an extra parameter comment.
                $error = 'Superfluous parameter comment';
                $phpcsFile->addError($error, $param['tag'], 'ExtraParamComment');
            }//end if

            if ($param['comment'] === '') {
                continue;
            }

            // Check number of spaces after the var name.
            $this->checkSpacingAfterParamType($phpcsFile, $param, $maxVar);

            // Param comments must start with a capital letter and end with the full stop.
            if (preg_match('/^(\p{Ll}|\P{L})/u', $param['comment']) === 1) {
                $error = 'Parameter comment must start with a capital letter';
                $phpcsFile->addError($error, $param['tag'], 'ParamCommentNotCapital');
            }

            $lastChar = substr($param['comment'], -1);
            if ($lastChar !== '.') {
                $error = 'Parameter comment must end with a full stop';
                $phpcsFile->addError($error, $param['tag'], 'ParamCommentFullStop');
            }
        }//end foreach

        $realNames = [];
        foreach ($realParams as $realParam) {
            $realNames[] = $realParam['name'];
        }

        // Report missing comments.
        $diff = array_diff($realNames, $foundParams);
        foreach ($diff as $neededParam) {
            $error = 'Doc comment for parameter "%s" missing';
            $data  = [$neededParam];
            $phpcsFile->addError($error, $commentStart, 'MissingParamTag', $data);
        }

    }//end processParams()

    /**
     * Check the spacing after the type of a parameter.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param array                       $param     The parameter to be checked.
     * @param int                         $maxType   The maxlength of the longest parameter type.
     * @param int                         $spacing   The number of spaces to add after the type.
     *
     * @return void
     */
    protected function checkSpacingAfterParamType(File $phpcsFile, $param, $maxType, $spacing=1)
    {
        // Check number of spaces after the type.
        $spaces = ($maxType - strlen($param['type']) + $spacing);
        if ($param['type_space'] !== $spaces) {
            $error = 'Expected %s spaces after parameter type; %s found';
            $data  = [
                $spaces,
                $param['type_space'],
            ];

            $fix = $phpcsFile->addFixableError($error, $param['tag'], 'SpacingAfterParamType', $data);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                $content  = $param['type'];
                $content .= str_repeat(' ', $spaces);
                $content .= $param['var'];
                $content .= str_repeat(' ', $param['var_space']);
                $content .= $param['commentLines'][0]['comment'];
                $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);

                // Fix up the indent of additional comment lines.
                foreach ($param['commentLines'] as $lineNum => $line) {
                    if ($lineNum === 0
                        || $param['commentLines'][$lineNum]['indent'] === 0
                    ) {
                        continue;
                    }

                    $diff      = ($param['type_space'] - $spaces);
                    $newIndent = ($param['commentLines'][$lineNum]['indent'] - $diff);
                    $phpcsFile->fixer->replaceToken(
                        ($param['commentLines'][$lineNum]['token'] - 1),
                        str_repeat(' ', $newIndent)
                    );
                }

                $phpcsFile->fixer->endChangeset();
            }//end if
        }//end if

    }//end checkSpacingAfterParamType()

    /**
     * Is the comment an inheritdoc?
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return boolean True if the comment is an inheritdoc
     */
    protected function isInheritDoc(PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $start = $phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPtr - 1);
        $end = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $start);

        $content = $phpcsFile->getTokensAsString($start, ($end - $start));

        return preg_match('#{@inheritdoc}#i', $content) === 1;
    } // end isInheritDoc()

    /**
     * Is the return statement matching?
     *
     * @param array $tokens    Array of tokens
     * @param int   $returnPos Stack position of the T_RETURN token to process
     *
     * @return boolean True if the return does not return anything
     */
    protected function isMatchingReturn($tokens, $returnPos)
    {
        do {
            $returnPos++;
        } while ($tokens[$returnPos]['code'] === T_WHITESPACE);

        return $tokens[$returnPos]['code'] !== T_SEMICOLON;
    }

}//end class
