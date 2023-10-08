<?php 
class Components {
    var $db_handler;
    var $__tableName__ = '';
    var $__fields__ = [];
    var $__labels__ = [];
    var $__conditions__ = [];
    var $__orderby__ = [];
    var $__groupby__ = [];
    
    var $__formName__ = 'listForm';
    var $__action__ = '';
    var $__buttons__ = [];
    var $__searchFields__ = [];
    var $isUseForm = True;
    var $isCart = True;
    var $page = 1;
    var $rowsPerPage = 20;
    var $showPageNum = 10;
    var $total = 0;
    var $totalPages = 0;
    var $start = 0;
    var $results;
    var $data = [];
                    
    public function __construct() {
        global $_lib;

        $this -> db_handler = new DBConn($_lib['db']['slave']);
        $temp = explode("?", $_SERVER['REQUEST_URI']);
        $url = $temp[0];
        $this -> __action__ = $url;
    }
    
    public function setJoin($name, $label, $condition="") {
        $obj = new $name;
        
        if(!check_blank($this -> __tableName__)) $this -> __tableName__ .= " JOIN ";
        $this -> __tableName__ .= $obj -> __tableName__." AS ".$label;
        
        foreach($obj -> __columns__ as $column) {
            if(check_blank($label)) $this -> setField($column -> field, $column -> field);
            else $this -> setField($label.'_'.$column -> field, $label.'.'.$column -> field);
        }
        
        if(!check_blank($condition)) $this -> setAndCondition($condition);
    }
    
    public function setLeftOuterJoin($name, $label, $condition) {
        $obj = new $name;
        
        if(!check_blank($this -> __tableName__)) $this -> __tableName__ .= " LEFT OUTER JOIN ";
        $this -> __tableName__ .= $obj -> __tableName__." AS ".$label;
        $this -> __tableName__ .= ' ON '.$condition;
        
        foreach($obj -> __columns__ as $column) {
            if(check_blank($label)) $this -> setField($column -> field, $column -> field);
            else $this -> setField($label.'_'.$column -> field, $label.'.'.$column -> field);
        }
    }
    
    public function setJoinTable($name, $label, $condition="") {
        if(!check_blank($this -> __tableName__)) $this -> __tableName__ .= " JOIN ";
        $this -> __tableName__ .= $name." AS ".$label;

		$fields = "";
		$results = $this -> db_handler -> query("SHOW COLUMNS FROM ".$name);
		while($data = $results -> fetch_array()) {
            if(check_blank($label)) $this -> setField($data[0], $data[0]);
            else $this -> setField($label.'_'.$data[0], $label.'.'.$data[0]);
		}
        
        if(!check_blank($condition)) $this -> setAndCondition($condition);
    }
    
    public function setLeftOuterJoinTable($name, $label, $condition) {
        if(!check_blank($this -> __tableName__)) $this -> __tableName__ .= " LEFT OUTER JOIN ";
        $this -> __tableName__ .= $name." AS ".$label;
        $this -> __tableName__ .= ' ON '.$condition;
        
		$fields = "";
		$results = $this -> db_handler -> query("SHOW COLUMNS FROM ".$name);
		while($data = $results -> fetch_array()) {
            if(check_blank($label)) $this -> setField($data[0], $data[0]);
            else $this -> setField($label.'_'.$data[0], $label.'.'.$data[0]);
		}
    }
    
    public function resetField() {
        $this -> __fields__ = [];
    }
    
    public function setField($name, $value) {
        $this -> __fields__[$name] = $value;
    }
    
    public function setFieldByClass($class, $label='') {
        $obj = new $class;
        
        foreach($obj -> __columns__ as $column) {
            if(check_blank($label)) $this -> setField($column -> field, $column -> field);
            else $this -> setField($label.'_'.$column -> field, $label.'.'.$column -> field);
        }
    }
    
    public function setLabel($index, $name, $value) {
        if(!isset($this -> __labels__)) $this -> __labels__ =  [];        
        if(!isset($this -> __labels__[$index])) $this -> __labels__[$index] =  [];
        
        $this -> __labels__[$index][$name] = $value;
    }
    
    public function setButton($json='') {
        if(is_string($json)) $json = jsondecode($json);
        
        array_push($this -> __buttons__, $json);
    }
    
    public function setCondition($json='') {
        if(is_string($json)) $json = jsondecode($json);
        
        array_push($this -> __conditions__, $json);
    }
    
    public function setAndCondition($condition='') {
        $obj = new stdClass();
        $obj -> type = 'AND';
        $obj -> condition = $condition;
        
        $this -> setCondition($obj);
    }
    
    public function setOrCondition($condition='') {
        $obj = new stdClass();
        $obj -> type = 'OR';
        $obj -> condition = $condition;
        
        $this -> setCondition($obj);
    }
    
    public function setSort($sort, $desc='asc') {
        $obj = new stdClass();
        $obj -> sort = $sort;
        $obj -> desc = $desc;
        
        array_push($this -> __orderby__, $obj);
    }
    
    public function setGroupby($key) {
        array_push($this -> __groupby__, $key);
    }
    
    public function setSearchField($name, $tag) {
        $data = new stdClass();
        $data -> name = $name;
        $data -> tag = $tag;
        
        array_push($this -> __searchFields__, $data);
    }

	public function getFields() {
        $fields = '';
        
        foreach($this -> __fields__ as $key => $value) {
			if(!check_blank($fields)) $fields .= ',';
            $fields .= $value.' AS '.$key;
        }
        
        if(check_blank($fields)) $fields = '*';
		
		return $fields;
	}

	public function getGroupby() {
        $groupby = '';
        
        foreach($this -> __groupby__ as $data) {
            if(!check_blank($groupby)) $groupby .= ',';
            $groupby .= $data;
        }
		
		return $groupby;
	}

	public function getOrderby() {
        $orderby = '';
               
        foreach($this -> __orderby__ as $data) {
            if(!check_blank($orderby)) $orderby .= ',';
            $orderby .= $data -> sort.' '.$data -> desc;
        }
		
		return $orderby;
	}
    
    public function getStart() {
        return ($this -> page - 1) * $this -> rowsPerPage;
    }
    
    public function getStartNo() {
        return $this -> total - $this -> getStart();
    }
    
    public function getRows() {
        $rows = $this -> getStart() + $this -> rowsPerPage;
        
        if($rows > $this -> total) return $this -> total - $this -> getStart();
        else return $this -> rowsPerPage;
    }
    
    public function getStartPage() {
        $startPage = (int)(($this -> page -1) / $this -> showPageNum) * $this -> showPageNum;
        $startPage = $startPage +1;
        
        return $startPage;
    }
    
    public function getStopPage() {
        $stopPage = $this -> getStartPage() + $this -> showPageNum -1;
        if($stopPage > $this -> totalPages) $stopPage = $this -> totalPages;
        
        return $stopPage;
    }
    
    public function getCondition() {
        $condition = '';
		//print_r($this -> __conditions__);
        foreach($this -> __conditions__ as $data) {
			if(isset($data -> type) && isset($data -> condition)) {
				if(!check_blank($condition)) $condition .= ' '.$data -> type.' ';
				$condition .= $data -> condition;
			}
        }
        
        return $condition;
    }

	public function getTotal($isEcho=false) {
        $fields = $this -> getFields();
        $condition = $this -> getCondition();
        $groupby = $this -> getGroupby();
                
        if(!check_blank($groupby)) {
            $query = new stdClass();
            $query -> table = "(SELECT ".$fields." FROM ".$this -> __tableName__;
            if(!check_blank($condition)) $query -> table .= ' WHERE '.$condition;
            $query -> table .= ' GROUP BY '.$groupby.') as x';
            $query -> field = 'count(*) AS count';
            
            return $this -> db_handler -> getData($query);
        } else {
            $query = new stdClass();
            $query -> table = $this -> __tableName__;
            $query -> field = 'count(*) AS count';
            $query -> where = $condition;

			return $this -> db_handler -> getData($query);
        }
	}

	public function getOnlyResults($start=0, $rows=0, $isEcho=false) {
        $fields = $this -> getFields();
        $condition = $this -> getCondition();
        $groupby = $this -> getGroupby();
        $orderby = $this -> getOrderby();
        
        $query = new stdClass();
        $query -> table = $this -> __tableName__;
        $query -> field = $fields;
        $query -> where = $condition;
        $query -> groupby = $groupby;
        $query -> orderby = $orderby;
        $query -> start = $start;
        $query -> rows = $rows;
        if($query -> rows <= 0) $query -> rows = 0;
        
        $results = $this -> db_handler -> selectQuery($query, $isEcho);
		
		if($this -> total <= 0 && isset($results -> num_rows)) $this -> total = $results -> num_rows;
        
        return $results;
    }
    
    public function getResults($isEcho=false) {
		if(!$isEcho) {
			$this -> total = $this -> getTotal();

			if($this -> rowsPerPage > 0) $this -> totalPages = (int)(($this -> total - 1) / $this -> rowsPerPage) + 1;
			else $this -> totalPages = 1;

			if($this -> totalPages < $this -> page) $this -> page = 1;
		}

        return $this -> getOnlyResults($this -> getStart(), $this -> getRows(), $isEcho);
    }
    
    public function displayLabel($isUl=false) {
        if(count($this -> __orderby__)) $orderby = $this -> __orderby__[0];
        else {
            $orderby = new stdClass();
            $orderby -> sort = '';
            $orderby -> desc = '';
        }
        
        $tag = "\n\t\t\t\t\t";
        
		if($isUl) $tag .= '<li class="list-head">';
		else $tag .= '<tr>';
        
		$columnIndex = 1;

		if($this -> isCart) {
            $tag .= "\n\t\t\t\t\t\t";
            if($isUl) $tag .= '<div class="column'.$columnIndex.'"><div class="cart "><input type="checkbox" name="isAllCart"/></div></div>';
			else $tag .= '<th><input type="checkbox" name="isAllCart"/></th>';

			$columnIndex ++;
        }
        
        foreach($this -> __labels__ as $labels) {
            //print_r($labels);
            $tag .= "\n\t\t\t\t\t\t";
            
			if($isUl) $tag .= '<div class="column'.$columnIndex.'">';
			else $tag .= '<th>';

            foreach($labels as $key => $value) {
                //print_r($key);
                if($value == '') $tag .= '<div>'.$key.'</div>';
                elseif($value == $orderby -> sort && $orderby -> desc == 'desc') $tag .= '<div class="'.$value.' bold"><a href="" onclick="$(this).parents(\'form\').ELCListSetSort(\''.$value.'\', \'asc\');return false;">'.$key.'</a> <a href="" onclick="$(this).parents(\'form\').ELCListSetSort(\''.$value.'\', \'asc\');return false;" class="direction">▲</a></div>';
                elseif($value == $orderby -> sort && $orderby -> desc == 'asc') $tag .= '<div class="'.$value.'"><a href="" onclick="$(this).parents(\'form\').ELCListSetSort(\''.$value.'\', \'desc\');return false;">'.$key.'</a> <a href="" onclick="$(this).parents(\'form\').ELCListSetSort(\''.$value.'\', \'desc\');return false;" class="direction">▼</a></div>';
                else $tag .= '<div class="'.$value.'"><a href="" onclick="$(this).parents(\'form\').ELCListSetSort(\''.$value.'\', \'desc\');return false;">'.$key.'</a> <a href="" onclick="$(this).parents(\'form\').ELCListSetSort(\''.$value.'\', \'desc\');return false;" class="direction">▼</a></div>';
            }
            if($isUl) $tag .= '</div>';
            else $tag .= '</th>';

			$columnIndex++;
        }
        
        $tag .= "\n\t\t\t\t\t";
        if($isUl) $tag .= '</li>';
		else $tag .= '</tr>';        
        
        return $tag;
    }
    
    public function displayHead($isUl=false) {
        $tag  = "\n";
        $tag .= '<div class="list">';
        $tag .= "\n\t";
        
		if($this -> isUseForm) $tag .= '<form action="'.$this -> __action__.'" method="get" class="listForm">';
        
		$tag .= '<input type="hidden" name="response" value="'.getVars('response').'">';
        $tag .= "\n\t\t";
        $tag .= '<div class="top">';
        $tag .= $this -> displaySeachFields();
        $tag .= "\n\t\t\t";
        $tag .= '<div class="search">';
        $tag .= $this -> displaySearch();
        $tag .= '</div>';
        $tag .= "\n\t\t\t";
        $tag .= '<div class="page_setup">';
        $tag .= "\n\t\t\t\t";
        $tag .= $this -> displayPageSetup();
        $tag .= '</div>';
        $tag .= "\n\t\t\t";
        $tag .= $this -> displaySetupDialog();
        $tag .= "\n\t\t";
        $tag .= '</div>';
        $tag .= "\n\t\t";
        $tag .= '<div class="middle">';
        $tag .= $this -> displayListHead($isUl);
        
        return $tag;        
    }

	public function displayListHead($isUl=false) {
        $tag = "\n\t\t\t";
		
		if($isUl) $tag .= '<ul class="list">';
        else $tag .= '<table class="list">';
        
        if(!$isUl) {
			$tag .= "\n\t\t\t\t";
			$tag .= '<thead>';
		}

        $tag .= $this -> displayLabel($isUl);

		if(!$isUl) {
			$tag .= "\n\t\t\t\t";
			$tag .= '</thead>';
		}
        
        return $tag;        
	}

	public function displayListFoot($isUl=false) {
        $tag  = "\n\t\t\t\t";
        
		if(!$isUl) $tag .= '<tfoot>';
        
		$tag .= "\n\t\t\t\t";
        
		if(!$isUl) $tag .= '</tfoot>';

        $tag .= "\n\t\t\t";
        
		if($isUl) $tag .= '</ul>';
		else $tag .= '</table>';
		
		$tag .= "\n\t\t";

		return $tag;
	}

    public function displayFoot($isUl=false) {
        $tag  = $this -> displayListFoot($isUl);
        $tag .= '</div>';
        $tag .= "\n\t\t";
        $tag .= '<div class="bottom">';
        $tag .= "\n\t\t\t";
        $tag .= '<div class="buttons">';
        $tag .= $this -> displayButtons();
        $tag .= "\n\t\t\t";
        $tag .= '</div>';
        $tag .= "\n\t\t\t";
        $tag .= '<div class="paging">';
        $tag .= $this -> displayPaging();
        $tag .= "\n\t\t\t";
        $tag .= '</div>';
        $tag .= "\n\t\t";
        $tag .= '</div>';
        $tag .= "\n\t";

        if($this -> isUseForm) $tag .= '</form>';
        
		$tag .= "\n";
        $tag .= '</div>';
        
        return $tag;
    }

	public function displaySearch() {
        $tag = "\n\t\t\t\t";
        $tag .= '<input type="text" name="keyword" value="'.getVars('keyword').'"/>';
        $tag .= "\n\t\t\t\t";
        $tag .= '<input type="submit" value="검색" class="button"/>';
        $tag .= "\n\t\t\t\t";
        $tag .= '<a href="" onclick="$(this).parents(\'form\').find(\'.setupDialog\').show();return false;" class="button">설정</a>';
        $tag .= "\n\t\t\t";
		$tag .= $this -> displayButtons();	
        $tag .= "\n\t\t\t";
		return $tag;
	}

	public function displayPageSetup() {
        $tag = "\n\t\t\t\t";
        $tag .= '<input type="text" name="page" value="'.$this -> page.'"/>';
        $tag .= ' / <span class="totalPages">'.$this -> totalPages.'</span>';
        $tag .= "\n\t\t\t\t";
        $tag .= '<a href="#" onclick="$(this).parents(\'form\').ELCListPrevPage();return false;" class="button">이전</a>';
        $tag .= "\n\t\t\t\t";
        $tag .= '<a href="#" onclick="$(this).parents(\'form\').ELCListNextPage();return false;" class="button">다음</a>';
        $tag .= "\n\t\t\t";
		
		return $tag;
	}

	public function displaySetupDialog() {
        $tag = '<div class="dialog setupDialog">';
        $tag .= "\n\t\t\t\t";
        $tag .= '<div class="head">';
        $tag .= "\n\t\t\t\t\t";
        $tag .= '<h1>리스트설정</h1>';
        $tag .= "\n\t\t\t\t\t";
        $tag .= '</div>';
        $tag .= "\n\t\t\t\t";
        $tag .= '<div class="body">';
        $tag .= "\n\t\t\t\t\t";
        $tag .= '<table class="form">';
        $tag .= "\n\t\t\t\t\t\t";
        $tag .= '<tr>';
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '<th>정렬기준</th>';
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '<td>';
        $tag .= "\n\t\t\t\t\t\t\t\t";
        $tag .= $this -> displaySelectSort();                                                                 
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '</td>';
        $tag .= "\n\t\t\t\t\t\t";
        $tag .= '</tr>';
        $tag .= "\n\t\t\t\t\t\t";
        $tag .= '<tr>';
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '<th>정렬방식</th>';
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '<td>';
        $tag .= "\n\t\t\t\t\t\t\t\t";
        $tag .= $this -> displaySelectDesc();
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '</td>';
        $tag .= "\n\t\t\t\t\t\t";
        $tag .= '</tr>';
        $tag .= "\n\t\t\t\t\t\t";
        $tag .= '<tr>';
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '<th>페이지당줄수</th>';
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '<td>';
        $tag .= "\n\t\t\t\t\t\t\t\t";
        $tag .= '<input type="text" name="rowsPerPage" value="'.$this -> rowsPerPage.'" class="won"/> <a href="" onclick="$(this).parents(\'form\').find(\'input[name=page]\').val(\'1\');$(this).parents(\'form\').find(\'input[name=rowsPerPage]\').val(\''.$this -> total.'\');return false;" class="button">전체</a>';
        $tag .= "\n\t\t\t\t\t\t\t";
        $tag .= '</td>';
        $tag .= "\n\t\t\t\t\t\t";
        $tag .= '</tr>';
        $tag .= "\n\t\t\t\t\t";
        $tag .= '</table>';
        $tag .= "\n\t\t\t\t";
        $tag .= '</div>';
        $tag .= "\n\t\t\t\t";
        $tag .= '<div class="foot">';
        $tag .= "\n\t\t\t\t\t";
        $tag .= '<input type="submit" value="적용" class="button">';
        $tag .= "\n\t\t\t\t";
        $tag .= '</div>';
        $tag .= "\n\t\t\t";
        $tag .= '</div>';
		
		return $tag;
	}

	public function displaySeachFields() {
		$tag = "";
        if(count($this -> __searchFields__)) {
            $tag .= '<ul class="form">';
            foreach($this -> __searchFields__ as $searchField) {
                $tag .= "\n\t\t\t\t";
                $tag .= '<li>';
                $tag .= "\n\t\t\t\t\t";
                $tag .= '<label>'.$searchField -> name.'</label>';
                $tag .= "\n\t\t\t\t\t";
                $tag .= '<span>'.$searchField -> tag.'</span>';
                $tag .= "\n\t\t\t\t";
                $tag .= '</li>';
            }
            $tag .= "\n\t\t\t";
            $tag .= '</ul>';
        }
		
		return $tag;
	}

	public function displayButtons() {
		$tag = '';

        foreach($this -> __buttons__ as $button) {
            $tag .= "\n\t\t\t\t";
            $tag .= '<a';
            if(isset($button -> id) && !check_blank($button -> id)) $tag .= ' id="'.$button -> id.'"';
            if(isset($button -> href) && !check_blank($button -> href)) $tag .= ' href="'.$button -> href.'"';
            if(isset($button -> target) && !check_blank($button -> target)) $tag .= ' target="'.$button -> target.'"';
            $tag .= ' class="button';
            if(isset($button -> class) && !check_blank($button -> class)) $tag .= ' '.$button -> class;
            $tag .= '"';
            if(isset($button -> option) && !check_blank($button -> option)) $tag .= ' '.$button -> option.'';
            if(isset($button -> onclick) && !check_blank($button -> onclick)) $tag .= ' onclick="'.$button -> onclick.'"';
            if(isset($button -> onchange) && !check_blank($button -> onchange)) $tag .= ' onchange="'.$button -> onchange.'"';
            $tag .= '>';
            $tag .= $button -> name;
            $tag .= '</a>';
        }

		return $tag;
	}

	public function displaySelectDesc() {
        if(count($this -> __orderby__)) $orderby = $this -> __orderby__[0];
        else {
            $orderby = new stdClass();
            $orderby -> sort = '';
            $orderby -> desc = '';
        }

		$tag = '<select name="desc">';
        $tag .= "\n\t\t\t\t\t\t\t\t\t";
        $tag .= '<option value="asc"'.($orderby -> desc != 'desc' ? ' selected="selected"' : '').'>정순</option>';
        $tag .= "\n\t\t\t\t\t\t\t\t\t";
        $tag .= '<option value="desc"'.($orderby -> desc == 'desc' ? ' selected="selected"' : '').'>역순</option>';
        $tag .= "\n\t\t\t\t\t\t\t\t";
        $tag .= '</select>';
		
		return $tag;
	}

	public function displaySelectSort() {
        $tag = '<select name="sort">';
        
        if(count($this -> __orderby__)) $orderby = $this -> __orderby__[0];
        else {
            $orderby = new stdClass();
            $orderby -> sort = '';
            $orderby -> desc = '';
        }
        
        foreach($this -> __labels__ as $labels) {
            foreach($labels as $key => $value) {
                $tag .= "\n\t\t\t\t\t\t\t\t\t";
                $tag .= '<option value="'.$value.'"'.($orderby -> sort == $value ? ' selected="selected"' : '').'>'.$key.'</option>';
            }
        }
            
        $tag .= "\n\t\t\t\t\t\t\t\t";
        $tag .= '</select>';                                                                   
		
		return $tag;
	}
    
    public function displayPaging() {
        $tag = "\n\t\t\t\t";
        if($this -> page != 1) {
            $tag .= '<a href="" onclick="$(this).parents(\'form\').ELCListMovePage(1);return false;" class="firstPage">&lt;&lt;</a>';
        }
        
        if($this -> page > 10) {
            $tag .= '<a href="" onclick="$(this).parents(\'form\').ELCListMovePage('.($this -> page - 10).');return false;" class="prevPage">&lt;</a>';
        }
        
        for($iPage = $this -> getStartPage(); $iPage <= $this -> getStopPage(); $iPage++) {
            if($iPage == $this -> page) $tag .= '<b>'.$iPage.'</b>';
            else $tag .= '<a href="" onclick="$(this).parents(\'form\').ELCListMovePage('.$iPage.');return false;">'.$iPage.'</a>';
        }
        
        if($this -> page < $this -> totalPages - 9) {
            $tag .= '<a href="" onclick="$(this).parents(\'form\').ELCListMovePage('.($this -> page + 10).');return false;" class="nextPage">&gt;</a>';
        }
        
        if($this -> totalPages > 0 && $this -> page != $this -> totalPages) {
            $tag .= '<a href="" onclick="$(this).parents(\'form\').ELCListMovePage('.($this -> totalPages).');return false" class="lastPage">&gt;&gt;</a>';
        }
        
        
        return $tag;
    }

	public function toJson() {
		$result = new stdClass();
		$result -> total = $this -> total;
		$result -> totalPages = $this -> totalPages;
		$result -> startPage = $this -> getStartPage();
		$result -> stopPage = $this -> getStopPage();
		$result -> page = $this -> page;
		$result -> rowsPerPage = $this -> rowsPerPage;
		$result -> sort = $this -> __orderby__[0] -> sort;
		$result -> desc = $this -> __orderby__[0] -> desc;
		$result -> data = [];
		foreach($this -> data as $obj) {
			array_push($result -> data, jsondecode($obj -> toJson()));
		}

		return jsonencode($result);
	}
}
?>