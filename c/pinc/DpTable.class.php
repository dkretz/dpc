<?PHP

error_reporting(E_ALL);
// $Id: DpTable.class.php,v 1.1 2009/07/28 17:15:54 dkretz Exp $

/**
 * Sort stuff doesn't do anything. It needs to either affect the row generation
 * or js with onload.
 */

/**
         js sorting
         note that <thead loads js/sort.js.
 */

/**
 * DpTable Class.
 *
 *
 *  <p>Initialize with a result-set-type array.</p>
 * </p>Use the AddColumn method to accumulate column information.
 *  <p>CSS classes.
 *      Sheld as an array.
 *      Load array on init
 *      implode to an expression "class=' ... '" upon emission
 */

/**
 * Magic CSS classes
 *   left, right, pager
 *   sortkey='fieldname'
 *   if table has class 'no_tr_for_th' then suppress <tr> for <th>
 *   <tr> for column headings has class 'colheadings'
 *    (i.e. table.dptable.no_tr_for_th tr.colheadings { display: none; }
 *   sorttable_nosort suppresses a column for sorting
 */
class DpTable
{
    private $_pager_template = "";
    private $_page_number = 1;
    private $_rows_per_page = 50;
    private $_table_title;
    private $_captions   = array() ;
    private $_columns    = array() ;
    private $_rows       = array() ;  // data
    private $_row_count  = 0;
    private $_suppress_column_headings = false;
    private $_class = null ;
    private $_id = null ;
    private $_rownum;
    private $_is_numbered = false;
    private $_is_paging = false;
	private $_is_qbe = false;
//    private $_rowfunc;

    /**
     * Constructor.
     *
     * @param string $id
     * @param string $class
     * @param string $title
     */

    function __construct($id = "", $class = "right dptable sortable", $title = "") {
        $this->_id          = $id;
        $this->_class       = preg_split("/\s+/", $class);
        if($title) {
            $this->_table_title = $title;
        }
    }

    /**
     * SetRows. Argument is resultset-type array (colname => value).
     *          or a function to return the same
     *
     * @param null|array $rows
     */

    public function SetRows($rows = null) {
        if(! is_array($rows)) {
            assert(false);
            dump($rows);
        }
        if($this->_row_count == 0) {
            $this->_row_count = count($rows);
        }
        if ( $this->IsPaging() && $this->PageNumber() > 0 && $this->RowsPerPage() > 0 ) {
            $r1          = ($this->PageNumber() - 1) * $this->RowsPerPage();
            $r1          = min($r1, count($rows));
            $r2          = $this->RowsPerPage();
            $r2          = min($r2, count($rows) - $r1);
            $this->_rows = array_slice( $rows, $r1, $r2 );
        } else {
            $this->_rows = $rows;
        }
    }

    /**
     * AddColumn.
     *
     * @param string $caption
     * @param string|null $colname
     * @param string|null $template
     * @param string|null $class
     */

    public function AddColumn( 
                        $caption,
                        $colname = null,
                        $template = null,
                        $class = null) {

        $this->_columns[] = new DpTableColumn(
                        $caption, 
                        $colname,
                        $template,
                        $class);
    }

    public function SetClass($class) {
        $this->_class = preg_split("/\s+/u", $class);
    }

    public function StrClass() {
        if($this->_suppress_column_headings) {
            $this->_class[] = "no_tr_for_th";
        }
        return implode(" ", $this->_class);
    }

    public function NoColumnHeadings() {
        $this->_suppress_column_headings = true;
    }

    public function SetId($id) {
        $this->_id = $id;
    }

    public function SetTitle($title) {
        $this->_table_title = $title;
    }

    public function Title() {
        return $this->_table_title;
    }

	public function SetQBE() {
		$this->_is_qbe = true;
	}
	public function ClearQBE() {
		$this->_is_qbe = false;
	}

    /**
     * echo. Generate the report.
     *
     */

    public function EchoTableNumbered() {
        $this->EchoTable(true) ;
    }

    /**
     * addCaption. Add a superheader above the header row.
     *
     */

    public function Captions() {
        return $this->_captions;
    }

    public function AddCaption( $text, $ncol = 1, $class = null, $tooltip = null) {
        $this->_captions[] = new DpTableCaption( $text, $ncol, $class, $tooltip ) ;
    }

    public function SetPage($page_num) {
        $this->_page_number = $page_num;
//        $this->SetRows();
    }

    public function PageForward() {
        if($this->PageNumber() < $this->PageCount()) {
            $this->_page_number++;
            $this->SetRows();
        }
    }

    public function PageBack() {
        if($this->PageNumber() > 1) {
            $this->_page_number--;
            $this->SetRows();
        }
    }


    public function SetRowsPerPage($rpp) {
        $this->_rows_per_page = $rpp ;
    }

    public function RowsPerPage() {
        return $this->_rows_per_page ;
    }

    public function PageNumber() {
        return $this->_page_number ;
    }

    public function PageCount() {
        return $this->RowsPerPage() <= 1
            ? null
			: ceil($this->RowCount() / $this->RowsPerPage());
    }

    public function SetPaging($pagenum = 0, $rowsperpage = 0) {
        if($pagenum > 0) {
            $this->_page_number = $pagenum;
        }
        if($rowsperpage > 0) {
            $this->_rows_per_page = $rowsperpage;
        }
        $this->_is_paging = true;
    }

    private function IsPaging() {
        return $this->_is_paging
                && $this->RowsPerPage() > 0
                && $this->PageNumber() > 0;
    }

    public function EchoTable($isnumbered = false) {
        // if fully automatic, derive columns from table
        $this->_is_numbered = $isnumbered;
        if(count($this->_columns) == 0) {
            if(! $this->_rows || ! is_array($this->_rows)
                        || count($this->_rows[0]) == 0)
                return ;
            $row = $this->_rows[0];
            if(! is_array($row) || count($row) == 0)
                return;
            foreach($row as $col => $val) {
                if(! is_int($col))
                    $this->AddColumn($col, $col);
            }
        }
        $str_out = "
            <table "
            . ($this->_id ? " id='{$this->_id}'" : "")
            . " class='{$this->StrClass()}'>\n";

        $colcount = $this->ColumnCount();

        // if there is a table title, it gets the first row. The first row only gets one cell.
        // The row <tr> gets class "table_title" and the cell gets "<th>" not "<td>".

        if($this->_table_title != "") {
            $str_out .= "<tr class='table_title'><th colspan='$colcount' class='table_title'>" . $this->_table_title . "</th></tr>\n";
        }
        if($this->IsPaging()) {
            $pagenum = $this->PageNumber();
            $pagecount = $this->PageCount();
	        $nmin = ($this->_page_number - 1) * $this->_rows_per_page + 1;
	        $nmax = min($this->PageNumber() * $this->RowsPerPage(), $this->_row_count);
	        $ntotal = $this->_row_count;
            $updisabled = ($this->PageNumber() <= 1 ? "disabled" : "");
            $dndisabled = ($this->PageNumber() >= $this->PageCount() ? "disabled" : "");
            $str_out .= "<tr><th colspan='$colcount' class='table_paging'>
                <div id='divpaging'>
                <div><input type='submit' value='Page Up' name='cmdPgUp' id='cmdPgUp' $updisabled></div>
                <div>Page {$pagenum} of {$pagecount}</div>
                <div><input type='submit' value='Page Down' name='cmdPgDn' id='cmdPgDn' $dndisabled></div>
                <div>Items $nmin to $nmax out of $ntotal</div>
                </div>
                </th></tr>\n";
        }

        // first the captions above the column headings
        $str_out .= $this->StrCaptions();
        // then the column headings
        $str_out .= $this->StrHeadings($isnumbered);
	    // then the QBE row
	    $str_out .= $this->StrQBE();

        $odd_even = true ;
        $this->_rownum 
            = ( $this->PageNumber() - 1 ) 
                            * $this->RowsPerPage();
        foreach($this->_rows as $row) {
            $this->_rownum++ ;
	        $row_id = (isset($row['row_id'])
						? " id='{$row['row_id']}'"
						: "");
            $odd_even = ! $odd_even;
            $str_out .= $odd_even
                ? "<tr $row_id class='odd'>\n"
                : "<tr $row_id class='even'>\n";

            if($isnumbered) {
                $str_out .= "<td class='right'>{$this->_rownum}</td>\n";
            }

            /** @var $col DpTableColumn */
            foreach($this->_columns as $col) {
                $str_out .= $col->EchoCell($row);
            }
            $str_out .= "</tr>\n";
        }
        if( $this->IsPaging() > 1 && $this->_pager_template)
            $str_out .= $this->_echo_pager() ;
        $str_out .=  "
            </table>\n";
        echo $str_out;
    }

    /**
     * _echo_captions.
     *
     *
     * @return string
     */

    // A row of Captions above Headings.
    // emits "<tr class='captions'>" ... captions ... "</tr>".
    // If a numbered table, prepend one blank cell "th".
    // Then concatenate the captions' StrCaption properties.
    private function StrCaptions() {
        if(count($this->_captions) == 0) {
            return "";
        }

        $s = "<tr class='captions'>\n";
        if($this->_is_numbered) {
            $s .= "<th> &nbsp; </th>\n";
        }
        foreach($this->_captions as $cap) {
            /** @var DpTableCaption $cap */
            $s .= $cap->StrCaption();
        }
        $s .= "</tr>  <!-- (class) captions --> \n";
        return $s;
    }

	private function StrQBE() {
		if(! $this->_is_qbe) {
			return "";
		}
		$s = "<tr class='qbe'>\n";
		if($this->_is_numbered) {
			$s .= "<td> &nbsp; </td>\n";
		}
		foreach($this->_columns as $col) {
			/** @var $col DpTableColumn */
			$qbename = "qbe" . $col->ColName();
			$s .= ("<td><input type='text' name='$qbename' id='$qbename'/></td>\n");
		}
		$s .= "</tr>\n";
		return $s;
	}

    // Headers Row - Column Headers
    // The row "<tr>" gets class "colheadings"
    // If it's numbered, a cell is added at the left with blank content
    // Then concatenate the StrCaption properties of the columns.
    private function StrHeadings($isnumbered = false) {

        $s = "<tr class='colheadings'>\n";
        if($isnumbered)
            $s .= "<th> &nbsp;</th>\n";
        foreach($this->_columns as $col) {
            /** @var $col DpTableColumn */
            $s .= $col->StrCaption();
        }
        $s .= "</tr>\n";
        return $s;
    }

    /**
     * _echo_row($row).
     *
     */


    private function _echo_pager() {
        if(is_callable($this->_pager_template)) {
            $str = call_user_func($this->_pager_template);
        }

        else {
            if($this->PageNumber() < $this->PageCount()) {
                return "";
            }

            if(! $this->_pager_template) {
                $this->_pager_template = _("Page %d of %d");
            }

            $str = sprintf($this->_pager_template,
                           $this->PageNumber(),
                           $this->PageCount());
        }
        
        return "<tr><td class='pager' colspan='{$this->ColumnCount()}'>
            $str
            </td></tr>\n";
    }

    public function IsNumbered() {
        return $this->_is_numbered;
    }
    public function ColumnCount() {
        return count($this->_columns) + ($this->IsNumbered() ? 1 : 0);
    }

    public function SetRowCount($n) {
        $this->_row_count = $n;
    }

    public function RowCount() {
        return $this->_row_count;
    }
}   // end class DpTable




class DpTableCaption
{
    var $_caption;
    var $_tooltip;
    var $_colcount ;
//    var $_align;
    var $_class = array();

    function __construct( $caption, $colcount = 1, $class = null, $tooltip = null) {
        $this->_tooltip = $tooltip;
        $this->_caption = $caption ;
        $this->_colcount = $colcount ;
        $this->_set_align( $caption ) ;
	    $this->_class = preg_split("/\s+/u", $class);
//        $this->_set_class( $class );
    }

    // 0 = none, 1 = asc, 2 = desc
    private function _set_align($cap) {
        switch(left($cap, 1)) {
            default :
//                $this->_align = "" ;
                $this->_caption = $cap ;
                return ;
            case '<' :
                $this->_class[] = "left";
                break ;
            case '>' :
                $this->_class[] = "right";
                break ;
            case '^' :
                $this->_class[] = "center";
                break ;
        }
        $this->_caption = substr( $cap, 1 ) ;
    }

//    private function _set_class( $class ) {
//        $this->_class = empty($class)
//            ? ""
//            : "class='$class'";
//    }

	private function StrClass() {
		return implode(" ", $this->_class);
	}

	private function StrColspan() {
		return " colspan='" . $this->_colcount . "'";
	}

    // The Caption emission for one Caption.
    // If it's a function:
    //    the function needs to emit an entire "<th....>...</th>" unit
    // Otherwise:
    // emit "<th" . " class='class class ...'"
    //            . " colspan='n'"
    //            . " title='tooltip'"
    //            . "</th".
    public function StrCaption() {
        $s = "";
	    if(is_callable($this->_class)) {
            $s .= call_user_func($this->_class, $this->_caption );
		}
        else {
            $s = "<th";
            if(count($this->_class) > 0) {
                $s .= " class='{$this->StrClass()}'";
            }
            if($this->_colcount > 1) {
                $s .= $this->StrColspan();
            }
            if($this->_tooltip) {
                $s .= " title='{$this->_tooltip}'";
            }
            $s .= ">{$this->_caption}</th>\n";
        }
        return $s;
    }
}

///////////////////////////////////////////////////////////////////

/**
     * DpTableColumn class.
     */

///////////////////////////////////////////////////////////////////

class DpTableColumn
{
    private $_caption;
    private $_colname;
    private $_template;
    private $_class;
    private $_sortkeyfield = null;

    /**
     * Initializer.
     *
     * caption is text column heading.
     * colname is the name of the field in the resultset row.
     * template is one of:
     * a. empty - the cell value is echoed.
     * b. an "sprintf" format string,
     * optionally including "%s" for the cell value.
     * c. if colname is empty, a function to echo any string
     * derivable from the $row. Notice a colname for the
     * column is redundant.
     * class is the name of the css class to use.
     * Public.
     * @param String $caption
     * @param null $colname
     * @param null $template
     * @param null $class
     */

    function __construct(
                $caption, 
                $colname = null, 
                $template = null, 
                $class = null) {
        $this->_colname = $colname;
	    $this->_caption = $caption;
	    $this->_class = preg_split("/\s/", $class) ;
        $this->_template = $template;
        $this->_set_align( $caption );
    }

    private function _set_align($cap) {
        if(empty($cap[0]))
            return;
        switch($cap[0]) {
            default :
                $this->_caption = $cap ;
                return ;
            case '<' :
                $this->_class[] = "left";
                break ;
            case '>' :
                $this->_class[] = "right";
                break ;
            case '^' :
                $this->_class[] = "center";
                break ;
        }
        $this->_caption = substr( $cap, 1 ) ;
    }

	private function StrClass() {
		if(count($this->_class) < 1) {
			return "";
		}
		return implode(" ", $this->_class);
	}

    public function strCaption() {
	    $str = "<th";
	    if(count($this->_class) > 0) {
		    $str .=  " class='{$this->StrClass()}'";
		}
	    $str .= ">$this->_caption</th>\n";
        return $str;
    }

    public function EchoCell($row) {
//	    dump($this);
//	    dump($row);
        $cname = $this->_colname;
        /** @var $template string */
        $template = $this->_template;

        if(! isset($template)) {
            if(isset($cname)) {

// -----------------------------------------------
//  problem here if $row[$cname] == null
//  which is apparently indistinguishable from ! isset($row[$cname])
// -----------------------------------------------

                // if(isset($row[$cname])) {
                if(array_key_exists($cname, $row)) {
                    $str = $row[$cname] ? $row[$cname] : ""; 
                }
                else {
                    $str = $cname;
                }
            }
            else {
                $str = "";
            }
        }
        else if(is_callable($template)) {
            if(isset($cname)) {
                if(array_key_exists($cname, $row)) {
                    $str = call_user_func($template, $row[$cname], $row);
                }
                else {
                    if(! isset($cname))
                        $cname = null;
                    $str = call_user_func($template, $cname, $row);
                }
            }
            else {
                /** @noinspection PhpParamsInspection */
                $str = call_user_func($template, $row);
            }
        }
        else {
        // template but not a function
            if(isset($cname)) {
                if(array_key_exists($cname, $row)) {
                    $str = sprintf($template, $row[$cname]);
                }
                else {
                    $str = sprintf($template, $cname);
                }
            }
            else {
                $str = sprintf($template);
            }
        }

        if($this->_sortkeyfield) {
            $val = $row[$this->_sortkeyfield];
            if(! $val) $val = 'Z';
            $sort = "sorttable_customkey='$val'";
        }
        else {
            $sort = "";
        }

//        return "<td {$sort} {$this->_class} {$this->_align}>$str</td>\n";
	    return "<td {$sort} class='" . implode(" ", $this->_class) . "'>$str</td>\n";
//	    return "<td {$sort} class='{$this->_class}'>{$str}</td>\n";
    }

    public function ColName() {
        return $this->_colname;
    }
}
