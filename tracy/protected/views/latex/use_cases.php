<?php

require('protected/views/latex/use_case_diagrams.php');

/* @var $this UseCaseController */
/* @var $model UseCase */

$this->breadcrumbs=array(
    'Use Cases'=>array('index'),
    "LaTeX",
);

$this->menu=array(
    array('label'=>'List Sources', 'url'=>array('source/index')),
    array('label'=>'Create ExternalSource', 'url'=>array('externalSource/create')),
    array('label'=>'Create Use Case', 'url'=>array('useCase/create')),
    array('label'=>'List UseCases', 'url'=>array('useCase/index')),
    );

if ( !$raw )
    echo "<h1>View Generated LaTeX for UseCases</h1>";

function display_uc($useCase, $raw)
{
    $string = '';
    $id = $useCase->public_id();
    $string .="\\hypertarget{{$id}}{}\n\\subsection{Caso d'uso $id: {$useCase->title}}";
    $string .= uc_diagrams\display_uc_diagram($useCase);
    $string .="\\begin{description}\n";
    $string .="\\item[Attori:] {$useCase->actors};\n";
    $string .="\\item[Scopo e descrizione:] {$useCase->description}
      \\item[Precondizione:] {$useCase->pre};\n";
      
    
    $prim = UseCaseEvent::model()->findAll(array(
        'order'=>'`order`',
        'condition'=>'category=1 and use_case=:uc',
        'params'=>array(':uc'=>$useCase->id_use_case)
    ));
    
    if ( count($prim) > 0 )
    {
        $string .="
        \\item[Flusso principale degli eventi:] \\ \n \\begin{enumerate}\n";
    
        foreach ( $prim as $event)
        {
            $string .="          \\item {$event->description}";
            if ($event->refers_to)
            {
                $child_id = $event->with('refersTo')->refersTo->public_id();
                $string .=" (\\hyperlink{{$child_id}}{{$child_id}})";
            }
            $string .= ";\n";
        }
        $string .="\n      \\end{enumerate}\n";
    }
    
    $alt = UseCaseEvent::model()->findAll(array(
        'order'=>'`order`',
        'condition'=>'category=3 and use_case=:uc',
        'params'=>array(':uc'=>$useCase->id_use_case)
    ));
    if ( count($alt) )
    {
        $string .="    \\item[Estensioni:]
      \\begin{enumerate}\n";
        foreach ( $alt as $event)
        {
            $string .="          \\item {$event->description}";
            if ($event->refers_to)
            {
                $child_id = $event->with('refersTo')->refersTo->public_id();
                $string .=" (\\hyperlink{{$child_id}}{{$child_id}})";
            }
            $string .= ";\n";
        }
        $string .="\n      \\end{enumerate}\n";
    }
    
    $alt = UseCaseEvent::model()->findAll(array(
        'order'=>'`order`',
        'condition'=>'category=4 and use_case=:uc',
        'params'=>array(':uc'=>$useCase->id_use_case)
    ));
    if ( count($alt) )
    {
        $string .="    \\item[Inclusioni:] \\ \n \\begin{enumerate}\n";
        foreach ( $alt as $event)
        {
            $string .="          \\item {$event->description}";
            if ($event->refers_to)
            {
                $child_id = $event->with('refersTo')->refersTo->public_id();
                $string .=" (\\hyperlink{{$child_id}}{{$child_id}})";
            }
            $string .= ";\n";
        }
        $string .="\n      \\end{enumerate}\n";
    }
    
    $alt = UseCaseEvent::model()->findAll(array(
        'order'=>'`order`',
        'condition'=>'category=2 and use_case=:uc',
        'params'=>array(':uc'=>$useCase->id_use_case)
    ));
    //$useCase->with(array('useCaseEvents'=>array('condition'=>'category=2','order'=>'order'))); 
    if ( count($alt) )//isset($useCase->useCaseEvents) && count($useCase->useCaseEvents) > 0 )
    {
        $string .="    \\item[Scenari Alternativi:] \\ \n \\begin{enumerate}\n";
        foreach ( $alt as $event)
        {
            $string .="          \\item {$event->description}";
            if ($event->refers_to)
            {
                $child_id = $event->with('refersTo')->refersTo->public_id();
                $string .=" (\\hyperlink{{$child_id}}{{$child_id}})";
            }
            $string .= ";\n";
        }
        $string .="\n      \\end{enumerate}\n";
    }
    $string .="    \\item[Postcondizione:] {$useCase->post}.\n";
    $string .="  \\end{description}\n";
    
    
    if ( $raw )
        echo "$string";
    else
        echo "<pre>$string</pre>";
        
    $useCase->with('useCases');
    if ( isset($useCase->useCases) )
        foreach ( $useCase->useCases as $child)
        {
            display_uc($child,$raw);
        }
}

$sourceArray = array();

$toplevel = UseCase::model()->findAll('parent is null');
@usort($toplevel, 'useCaseCompare');
foreach ($toplevel as $useCase)
{
    display_uc($useCase,$raw);
}


  
?> 
