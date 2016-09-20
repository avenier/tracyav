<?php

namespace uc_diagrams;

// How to draw an actor
class Actor {

    private $name;
    public $height;

    public function __construct($name) {
        $this->name = $name;
        $this->height = 2;
    }

    public function latex($x, $y) {
        return "\t\t\\umlactor[x=$x, y=$y]{{$this->name}}\n";
    }

}

// Classe base che rappresenta un qualsiasi
// use case
class BaseUseCase {

    public $id;
    public $public_id;
    public $title;
    public $height;

    public static function line_height($string) {
        $line_length = 20; // how many characters can one line fit (4cm = ~20 chars)
        $line_coeff = 0.5; // how much should an extra line be height (line height of ~0.35 + padding)
        return ceil(strlen($string) / $line_length) * $line_coeff;
    }

    public function __construct($use_case) {
        $this->id = $use_case->id_use_case;
        $this->public_id = $use_case->public_id();
        $this->title = $use_case->title;

        $base_height = 1; // base vertical padding (~1 to be generous)
        $string = "{$this->public_id}: {$this->title}";
        $this->height = $base_height  + self::line_height($string);
    }

    public function latex($x, $y) {
        $string = "";
        $string .= "\t\t\t\\umlusecase[x=$x, y=$y, fill=white, width=4cm, name={$this->id}]";
        $string .= "{\\textbf{{$this->public_id}:} {$this->title}}\n";
        return $string;
    }

    // Used for equality:
    public function __toString() { return "{$this->id}"; }

}

// Normal use case that draw itself connected to
// the actors it is used by
class UseCase extends BaseUseCase {

    private $actors;

    public function __construct($use_case) {
        parent::__construct($use_case);
        $this->actors = $use_case->actors_list();
    }

    public function latex($x, $y) {
        $string = "";
        $string .= parent::latex($x, $y);
        foreach ($this->actors as $a)
            $string .= "\t\t\t\\umlassoc{{$a}}{{$this->id}}\n";
        return $string;
    }

}

// Generalization children attached to
// it's parent used case
class ChildUseCase extends BaseUseCase {

    private $gparent_id;

    public function __construct($use_case) {
        parent::__construct($use_case);
        $this->gparent_id = $use_case->gparent0->id_use_case;
    }

    public function latex($x, $y) {
        $string = "";
        $string .= parent::latex($x, $y);
        $string .= "\t\t\t\\umlinherit{{$this->id}}{{$this->gparent_id}}\n";
        return $string;
    }

}

// Extend of another use case
class ExtendUseCase extends BaseUseCase {

    private $extended_id;
    private $extend_note;

    public function __construct($extended_use_case, $use_case) {
        parent::__construct($use_case);
        $this->extended_id = $extended_use_case->id_use_case;
        $this->extend_note = $use_case->pre;

        // Set the maximum between the note text and use case text as the
        // height of the overall use case
        // The arrow also has an height that needs to be considered
        $base_height = 1; // base vertical padding (~1 to be generous)
        $note_height = $base_height  + parent::line_height($this->extend_note);
        $note_arrow_height = 0.5;
        $this->height = max($note_height, $this->height) + $note_arrow_height;
    }

    public function latex($x, $y) {
        $string = parent::latex($x, $y);
        $extend_name = "ext-{$this->id}-{$this->extended_id}";
        $string .= "\t\t\t\\umlextend[name=$extend_name]{{$this->id}}{{$this->extended_id}}\n";

        $note_text = $this->extend_note;
        $note_x = $x + 8;
        $arm_offset = - ($this->height / 2) - 0.5;
        $note_settings = "x=$note_x, y=$y, fill=white, width=4cm, geometry=|-|, arm={$arm_offset}cm";
        $string .= "\t\t\t\\umlnote[$note_settings]{{$extend_name}-1}{{$note_text}}\n";
        return $string;
    }

}

// Include of another use case
class IncludeUseCase extends BaseUseCase {

    private $included_id;

    public function __construct($included_use_case, $use_case) {
        parent::__construct($use_case);
        $this->included_id = $included_use_case->id_use_case;
    }

    public function latex($x, $y) {
        $string = "";
        $string .= parent::latex($x, $y);
        $string .= "\t\t\t\\umlinclude{{$this->id}}{{$this->included_id}}\n";
        return $string;
    }

}

class View {
    public $caption;
    public $label;

    public function __construct($use_case) {
        $this->caption = "\\textbf{".$use_case->public_id()."}: {$use_case->title}";
        $this->label = $use_case->public_id();
    }
}

// View of the diagram that shows the list of
// sub use cases of a particulat use case
class SubsView extends View {

    // Actors to display for this
    // view
    public $actors = array();

    // Columns to display for this view.
    // Each column is an array of use cases
    public $columns = array();

    public function __construct($use_case) {
        parent::__construct($use_case);

        $actors = $use_case->children_actors_list();
        foreach ($actors as $a)
            $this->actors[] = new Actor($a);

        $subs = $use_case->children();
        $this->columns[] = array();
        foreach ($subs as $uc)
            $this->columns[0][] = new UseCase($uc);
    }

}

// View of the diagram that shows the list of
// extends, includes and specializations of a particulat use case
class DetailView extends View {

    private $subs = array();

    // Actors to display for this
    // view
    public $actors = array();

    // Columns to display for this view.
    // Each column is an array of use cases
    public $columns = array();

    public function __construct($use_case) {
        parent::__construct($use_case);

        $actors = $use_case->actors_list();
        foreach ($actors as $a)
            $this->actors[] = new Actor($a);

        // Add main use case
        $this->columns[] = array(new UseCase($use_case));

        // Add all other use cases
        $all = array();
        $children = $use_case->specializations;
        $extends = $use_case->extend_list();
        $includes = $use_case->include_list();
        foreach ($children as $uc)
            $all[] = new ChildUseCase($uc);
        foreach ($extends as $uc)
            $all[] = new ExtendUseCase($use_case, $uc);
        foreach ($includes as $uc)
            $all[] = new IncludeUseCase($use_case, $uc);
        $this->columns[] = array_unique($all);
    }

}



class Diagram {

    private static function height_of($array) {
        $total = 0;
        foreach ($array as $elem) {
            $total += $elem->height;
        }
        return $total;
    }

    private $system_name;
    private $view;

    public function __construct($system_name, $view) {
        $this->system_name = $system_name;
        $this->view = $view;
    }

    private function actors_latex() {
        // Make it so all actors are vertically
        // centered around y = 0
        $y_padding = 4;
        $string = "";
        $x = 0;
        $y = - (self::height_of($this->view->actors) + ($y_padding * (count($this->view->actors) - 1))) / 2;
        foreach ($this->view->actors as $a) {
            $h = $a->height;
            $string .= $a->latex($x, $y + ($h / 2));
            $y += $a->height;
            $y += $y_padding;
        }
        return $string;
    }

    private function system_latex() {
        $x_padding = 10;
        $y_padding = 2;
        $string = "";
        $x = $x_padding / 2; // starting from the very left after the actors
        foreach ($this->view->columns as $column) {
            $rcolumn = array_reverse($column);
            $y = - (self::height_of($rcolumn) + ($y_padding * (count($rcolumn) - 1))) / 2;
            foreach ($rcolumn as $elem) {
                $h = $elem->height;
                $string .= $elem->latex($x, $y + ($h / 2));
                $y += $elem->height;
                $y += $y_padding;
            }
            $x += $x_padding;
        }
        return $string;
    }

    public function latex() {
        $string = "\n\t\\begin{figure}[H]\n";
        $string .= "\t\t\\centering\n";
        $string .= "\t\t\\begin{resizedtikzpicture}{\\textwidth}\n";
        $string .= $this->actors_latex();

        $string .= "\t\t\\begin{umlsystem}[x=0, fill=lightgray!20]{{$this->system_name}}\n";
        $string .= $this->system_latex();
        $string .= "\t\t\\end{umlsystem}\n";

        $string .= "\t\t\\end{resizedtikzpicture}\n";
        $string .= "\t\t\\caption{{$this->view->caption}}\n";
        $string .= "\t\t\\label{{$this->view->label}}\n";
        $string .= "\t\\end{figure}\n";
        return $string;
    }
}

function display_uc_diagram($use_case) {
    $ret = '';
    $subs = $use_case->children();
    if (!empty($subs)) {
        $subsView = new SubsView($use_case);
        $subsDiagram = new Diagram("Quizzipedia", $subsView);
        $ret .= $subsDiagram->latex();
    }

    $children = $use_case->specializations;
    $extends = $use_case->extend_list();
    $includes = $use_case->include_list();
    if (!empty($children) || !empty($extends) || !empty($includes)) {
        $detailView = new DetailView($use_case);
        $detailDiagram = new Diagram("Quizzipedia", $detailView);
        $ret .= $detailDiagram->latex();
    }
    return $ret;
}
