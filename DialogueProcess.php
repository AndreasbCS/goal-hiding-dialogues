<?php
// DialogueProcess.php
// -------------------

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

include('DialogueManager.php');
include('QbafManager.php');
include('DependencyGraph.php');
include('DialogueLog.php');

// Settings
$qbafs = [];
$discount = 0.2;
$sensitivityInterval = array(
	'lowerBound' => 0.5, 
	'upperBound' => 0.7
	// For evaluation: without interval so that all topics are valid
	//'lowerBound' => 0.0, 
	//'upperBound' => 1.0	
);

// Get knowledge bases
$dependencyGraph_json = file_get_contents("evaluation1-dependencyGraph.json");
$qbaf_json = file_get_contents("evaluation1-QBAF.json");
$dialogueScript_json = file_get_contents("evaluation1-dialogueScript.json");

// Dependency graph (Specifies available topics, beliefs and belief-topic dependencies)
$dependencyGraph = DependencyGraph::graphFromJson($dependencyGraph_json);

// Initial QBAF
$qbaf = Qbaf::qbafFromJson($qbaf_json);

$beliefs = $dependencyGraph->getBeliefs();
$topics = $dependencyGraph->getTopics();
$goalTopic = end($topics); // The last topic in the array is considered the goal.

// The dialogue log is managing the dialogue history and print outs.
$dialogueLog = new DialogueLog();

// The qbaf manager keeps track of the set of QBAFs connected to dialogue states.
$qbafManager = new QbafManager($qbafs, $discount, $dialogueLog);
$qbafManager->addQbaf($qbaf);

// The dialogue manger processes the dialogue.
$dialogueManager = new DialogueManager(
							$topics, 
							$beliefs, 
							$qbafManager, 
							$dependencyGraph, 
							$discount, 
							$sensitivityInterval,
							$dialogueLog);

// Manage input from GUI
$dialogueHistory = [];
$print = TRUE;

if(isset($_POST)){
	$formData = $_POST; // Assuming the form data is received through POST
		
	if(isset($formData["dialogueMoves"]) && $formData["dialogueMoves"] != ''){
		$dialogueHistory = json_decode($formData["dialogueMoves"], TRUE);
	}
}



// Process dialogue history			
// Example-1 JSON input as an array.

/*
$dialogueHistory = [
	["agent" => "seeker", "moveType" => "open_topic", "utterance" => "t0"],
	["agent" => "respondent", "moveType" => "assert_belief", "utterance" => "b0"],
	["agent" => "respondent", "moveType" => "assert_belief", "utterance" => "b3"],
	["agent" => "seeker", "moveType" => "open_topic", "utterance" => "t1"],
	["agent" => "respondent", "moveType" => "assert_belief", "utterance" => "b5"],
	["agent" => "respondent", "moveType" => "assert_belief", "utterance" => "b7"],
	["agent" => "seeker", "moveType" => "open_topic", "utterance" => "t4"]
];*/	

//$input = "t0, b1, t1, b4, t2,  b8, t3, b12, t4";

if(isset($_GET['dialogueHistory'])){ 

	$input = $_GET['dialogueHistory']; 
	$dialogueHistory = convertToDialogueHistory($input);
}

$dialogueManager->processDialogue($dialogueHistory, $print);

// Get dialogue after being processed. Beliefs from the input that does not belong to the knowledge base will be removed.
$dialogueHistory = $dialogueManager->getDialogue();

// Suggested next move
$nextTopic = $dialogueManager->selectTopic($goalTopic);

if($nextTopic['utterance'] == $goalTopic){ $isGoalMessage = "(Goal Topic)"; }else{ $isGoalMessage = ""; }

if(isset($nextTopic['utterance'])){ $suggestedTopisMessage = $nextTopic['utterance']; }else{ $suggestedTopisMessage = "No reachable topics"; }

$dialogueLog->addToLogMessage("Suggested topic: " . $suggestedTopisMessage ." ".$isGoalMessage);

$message = "<br><br>";
$message .= "Dependency Graph: ";
$message .= json_encode($dependencyGraph);
$message .= "<br><br>";
$message .= "Initial QBAF: ";
$message .= $qbaf_json;
$message .= "<br><br>";

$dialogueLog->addToLogMessage($message);	

$dialogueLogMessage = $dialogueLog->getLogMessage();

$return_object = array(
	"dialogueLog" => $dialogueLogMessage,
	"dialogueHistory" => $dialogueHistory,
	"nextTopic" => $nextTopic['utterance']
);

// For system evaluation // Deactivate before user testing
echo print_r($dialogueLogMessage);

// Current dialogue hstory and the next topic is sent to the GUI.
echo json_encode($return_object);



function convertToDialogueHistory($input) {
    // Split the input string by comma
    
	$input = str_replace(" ","",$input);
	$parts = explode(",", $input);

    $dialogueHistory = [];

    foreach ($parts as $part) {
        // Extract the character representing the move type (either 't' or 'b')
        $moveType = substr($part, 0, 1);
        // Extract the number representing the utterance
        $utterance = substr($part, 1);

        // Determine agent based on move type
        if ($moveType === 't') {
            $agent = 'seeker';
            $moveType = 'open_topic';
        } else if ($moveType === 'b') {
            $agent = 'respondent';
            $moveType = 'assert_belief';
        }

        // Build the dialogue entry
        $dialogueEntry = [
            "agent" => $agent,
            "moveType" => $moveType,
            "utterance" => $part
        ];

        // Add the dialogue entry to the dialogue history array
        $dialogueHistory[] = $dialogueEntry;
    }

    // Return the dialogue history array
    return $dialogueHistory;
}


?>