<?php

class DialogueLog {
    private $dialogueLog = array();
	private $logMessage;

    public function addDialogueState($dialogueState) {
        $this->dialogueLog[$dialogueState] = array(
            'dialogueState' => $dialogueState,
            'dialogue' => array(),
            'moves' => array(),
            'qbaf' => array(),
            'attacks' => array(),
            'supports' => array(),
            'topicStrengths' => array()
        );
    }

	public function addToLogMessage($message){
		$this->logMessage .= $message;
	}

	public function getLogMessage(){
		return $this->logMessage;
	}

    public function addDialogue($dialogueState, $dialogue) {
        if (isset($this->dialogueLog[$dialogueState])) {	
			array_shift($dialogue); // Remove first element, the "empty move".
            $this->dialogueLog[$dialogueState]['dialogue'] = $dialogue;
        }
    }

    public function addMoves($dialogueState, $moves) {
        if (isset($this->dialogueLog[$dialogueState])) {
            $this->dialogueLog[$dialogueState]['moves'] = $moves;
        }
    }

    public function addQbaf($dialogueState, $qbaf) {
        if (isset($this->dialogueLog[$dialogueState])) {
            $this->dialogueLog[$dialogueState]['qbaf'] = $qbaf;
        }
    }

    public function addAttacks($dialogueState, $attacks) {
        if (isset($this->dialogueLog[$dialogueState])) {
            $this->dialogueLog[$dialogueState]['attacks'] = $attacks;
        }
    }

    public function addSupports($dialogueState, $supports) {
        if (isset($this->dialogueLog[$dialogueState])) {
            $this->dialogueLog[$dialogueState]['supports'] = $supports;
        }
    }

    public function addTopicStrengths($dialogueState, $topicStrengths) {
        if (isset($this->dialogueLog[$dialogueState])) {
            $this->dialogueLog[$dialogueState]['topicStrengths'] = $topicStrengths;
        }
    }

    public function createLog() {
        $message = '<table class="logTable">
				<tr>
				<th>State</th>
				<th>Dialogue</th>
				<th>QBAF</th>
				<th>Attacks</th>
				<th>Supports</th>
				<th>Topic Strengths</th>
				<th>Goal Strength</th>
				</tr>';
          
        foreach ($this->dialogueLog as $entry) {
            $message .= '<tr>';
            $message .= '<td>' . $entry['dialogueState'] . '</td>';
            $message .= '<td><pre>'. json_encode($entry['dialogue'], true) . '</pre></td>';
            $message .= '<td><pre>'. json_encode($entry['qbaf'], true) . '</pre></td>';
            $message .= '<td><pre>'. json_encode($entry['attacks'], true) . '</pre></td>';
            $message .= '<td><pre>'. json_encode($entry['supports'], true) . '</pre></td>';
            $message .= '<td><pre>'. json_encode($entry['topicStrengths'], true) . '</pre></td>';
            $message .= '<td>'. end($entry['topicStrengths']) . '</td>';
            $message .= '</tr>';
        }
        
        $message .= '</table>';
		
		//$this->addToLogMessage($message); // appending to log message.
		$this->logMessage = $message; // instead of appending, replace the log message to make sure it is only the latest table logged.
    }
}

?>