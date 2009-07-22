<?php
/**
 * "Unified" diff renderer.
 *
 * This class renders the diff in classic "unified diff" format.
 *
 * $Horde: framework/Text_Diff/Diff/Renderer/unified.php,v 1.2 2004/01/09 21:46:30 chuck Exp $
 *
 * @package Text_Diff
 */

require_once 'Text/Diff.php';
require_once 'Text/Diff/Renderer.php';

class Bug_Diff_Renderer extends Text_Diff_Renderer {

    /**
     * Number of leading context "lines" to preserve.
     */
    var $_leading_context_lines = 4;

    /**
     * Number of trailing context "lines" to preserve.
     */
    var $_trailing_context_lines = 4;

    function __construct($d)
    {
        $this->diff = $d;
        parent::Text_Diff_Renderer();
    }

    function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        $removed = $xlen - $ylen;
        if ($removed > 0) {
            return '<span class="diffheader">Line ' . $xbeg . ' (now ' . $ybeg . '), was ' .
                $xlen . ' lines, now ' . $ylen . ' lines</span>';
        }
    }

    function _added($lines)
    {
        array_walk($lines, create_function('&$a,$b', '$a=htmlspecialchars($a);'));
        return '<span class="newdiff"> ' . implode("</span>\n<span class='newdiff'> ", $lines) .
            '</span>';
    }

    function _context($lines)
    {
        array_walk($lines, create_function('&$a,$b', '$a=htmlspecialchars($a);'));
        return "\n" . parent::_context($lines);
    }
    
    function _deleted($lines)
    {
        array_walk($lines, create_function('&$a,$b', '$a=htmlspecialchars($a);'));
        return '<span class="olddiff"> ' . implode("</span>\n<span class='olddiff'> ", $lines) .
            '</span>';
    }

    function _changed($orig, $final)
    {
        return $this->_deleted($orig) . "\n" . $this->_added($final);
    }

    function render()
    {
        return parent::render($this->diff);
    }
}
