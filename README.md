# Goal-Hiding Dialogues (GHD) Framework

A PHP implementation of the formal framework for goal-hiding information-seeking dialogues. This code is used in the empirical evaluation presented in the paper *Goal-hiding Information-seeking Dialogues: A Formal Framework* (under review).

## Installation
Clone the repository and ensure PHP is installed in your environment.

```bash
git clone https://github.com/AndreasbCS/goal-hiding-dialogues-framework.git
cd goal-hiding-dialogues-framework
```

## Files

- `DialogueManager.php`: Tracks and manages dialogue states.
- `QbafManager.php`: Implementation of Quantitative Bipolar Argumentation Framework (QBAF). Manages multiple QBAF instances, calculating argument strength changes over time.
- `DependencyGraph.php`: Manages the belief-topic dependency graph.
- `evaluation1-dependencyGraph.json`: Specifies a particular dependency graph with belief-topic realtions.
- `evaluation1-dialogueScript.json`: Specifies a particular dialogue script with topics and belief mappings for dialogue sequences.
- `evaluation1-QBAF.json`: Specifies a particular QBAF structure.
- `DialogueProcess.php`: Initialization file for the experiment.

![UML](https://github.com/AndreasbCS/goal-hiding-dialogues-framework/blob/main/figures/UML-Goal-hiding-implementation.pdf)
