
$(document).ready(function() {
    // Define a global array to keep track of dialogue moves
    window.dialogueMoves = [];
	window.currentTopic = "t0";

    // Initial chat interface generation
    generateChatInterface(window.currentTopic);

    // Handle form submission
    $("#chat-form").submit(function (event) {
        event.preventDefault(); // Prevent form from submitting normally

        // Get the selected beliefs/answers
        const selectedBeliefs = [];
        $("input[type='radio']:checked, input[type='checkbox']:checked").each(function() {
            const belief = $(this).val();
            selectedBeliefs.push(belief);
        });

		// Check if the current topic is in the dialogue history before the selected beliefs
		const lastMove = dialogueMoves.length > 0 ? dialogueMoves[dialogueMoves.length - 1] : null;
		if (lastMove && lastMove.agent === "seeker" && lastMove.moveType === "open_topic" && lastMove.utterance === window.currentTopic) {
			// The topic is already in the dialogue history, no need to add it again
		} else {
			// Add the current topic to dialogueMoves if not present
			addDialogueMove("seeker", "open_topic", window.currentTopic);
		}        
		
		// Add selected beliefs/answers to the dialogueMoves array
        selectedBeliefs.forEach(belief => {
            addDialogueMove("respondent", "assert_belief", belief);
        });

        // Serialize dialogueMoves array
        const serializedDialogues = JSON.stringify(dialogueMoves);

        // Serialize form data
        let formData = $(this).serialize();

        // Include serialized dialogueMoves in form data
        formData += "&dialogueMoves=" + encodeURIComponent(serializedDialogues);

        // Send form data to DialogueProcess.php using AJAX		
		$.ajax({
			type: "POST",
			url: "DialogueProcess.php",
			data: formData,
			dataType: "json", // Specify that the response should be treated as JSON
			success: function(response) {
				
				if(response.nextTopic !== null){
					// Accessing properties of the JSON response object
					const nextTopic = response.nextTopic.trim();
					const dialogueHistory = response.dialogueHistory;
					const dialogueLogMessage = response.dialogueLog;

					// Redraw the chat interface with the new topic and updated dialogue history
					generateChatInterface(nextTopic);
				
					$('#dialogueLog').empty();
					$('#dialogueLog').append(dialogueLogMessage);
				}
				else{
					// No more valid topics are reachable from this state, given the repsondents answers 
					// (nextTopic is null).
					
					// Accessing properties of the JSON response object
					const dialogueHistory = response.dialogueHistory;
					const dialogueLogMessage = response.dialogueLog;					
					
					// Draw a final screen.
					generateCompletionInterface();
					
					$('#dialogueLog').empty();
					$('#dialogueLog').append(dialogueLogMessage);
				}
			},
			error: function(error) {
				console.error("Error sending form data:", error);
			}
		});

		
    });
});

// Function for drawing a chat interface based on a topic
async function generateChatInterface(topic) {
	
	window.currentTopic = topic;
	
    try {

        const response = await fetch('evaluation1-dialogueScript.json');
        const dialogueData = await response.json();

        const dialogueScripts = dialogueData.dialogueScripts;

        const topicScript = dialogueScripts.find(script => script.topic === topic);

        if (!topicScript) {
            console.log("Topic not found");
            return;
        }
			
        const chatContainer = $("#chat-container");
        chatContainer.empty(); // Clear existing content

        const topicHeader = $("<h2></h2>").append(topicScript.text +' <span class="extra-info">('+topicScript.topic+')</span>');
        chatContainer.append(topicHeader);
		
        topicScript.script.forEach(questionData => {
			const questionWrapper = $('<div class="card card-body bg-light form-group"></div>');
			chatContainer.append(questionWrapper);
			
			const questionHeader = $("<h3></h3>").text(questionData.text);
            questionWrapper.append(questionHeader);
	
		
            if (questionData.answerType === "radio") {
                // Create radio buttons
                questionData.answers.forEach(answerData => {
                    const radioButton = createRadioButton(answerData, questionData.question);
                    const inputWrapper = $('<div class="form-control"></div>');					
					inputWrapper.append(radioButton);
					inputWrapper.append('<span class="side-label">'+ answerData.text +' <span class="extra-info">('+answerData.belief+')</span></span>');
                    questionWrapper.append(inputWrapper);
                });
            } else if (questionData.answerType === "checkbox") {
                // Create checkboxes
                questionData.answers.forEach(answerData => {
                    const checkbox = createCheckbox(answerData);
                    const inputWrapper = $('<div class="form-control"></div>');
					inputWrapper.append(checkbox);
                    inputWrapper.append('<span class="side-label">'+ answerData.text +' <span class="extra-info">('+answerData.belief+')</span></span>');
					questionWrapper.append(inputWrapper);
                });
            } else if (questionData.answerType === "freetext") {
                // Create a free text input
                const freeTextInput = createFreeTextInput();
                questionWrapper.append(freeTextInput);
				
				questionData.answers.forEach(answerData => {
					questionWrapper.append('<div class="side-label">'+ answerData.text +' <span class="extra-info">('+answerData.belief+')</span></div>');
				});
            }
        });
		
		const submitButton = $('<input type="submit" name="submit" value="Next Topic" class="btn btn-primary">');
		chatContainer.append(submitButton);
		
    } catch (error) {
        console.error('Error fetching or processing JSON:', error);
    }
}

// Function for generating a final screen after no more topics are available
async function generateCompletionInterface() {
    try {
        const chatContainer = $("#chat-container");
        chatContainer.empty(); // Clear existing content

        const completionScreen = $("<div class='completion-screen'></div>");
        chatContainer.append(completionScreen);

        const completionHeader = $("<h2>Survey Completed</h2>");
        completionScreen.append(completionHeader);

        const completionMessage = $("<p>Thank you for your answers.</p>");
        completionScreen.append(completionMessage);

        const endSurveyButton = $('<button id="end-survey" class="btn btn-primary">End Survey</button>');
        completionScreen.append(endSurveyButton);

        // Attach a click event handler to the end survey button
        endSurveyButton.on("click", function() {
            // Reload the page to reset the survey
            location.reload();
        });
    } catch (error) {
        console.error('Error processing completion interface:', error);
    }
}




// Function to generate a radio button
function createRadioButton(answerData, question) {
    const radioButton = $('<input type="radio" class="form-check-input">')
        .prop("name", "radio-" + question)
        .val(answerData.belief);
    return radioButton;
}

// Function to generate a checkbox
function createCheckbox(answerData) {
    const checkbox = $('<input type="checkbox" class="form-check-input">')
        .prop("name", answerData.belief)
        .val(answerData.belief);
    return checkbox;
}

// Function to generate a textbox
function createFreeTextInput() {
    const freeTextInput = $('<input type="text" class="form-control">')
        .prop("placeholder", "Type your answer here");
    return freeTextInput;
}

// Function to add a dialogue move to the array
function addDialogueMove(agent, moveType, utterance) {
	const move = {
		agent: agent,
		moveType: moveType,
		utterance: utterance
	};
	dialogueMoves.push(move);
}

