# os25d-level-compiler
Oldschool 2.5D Engine : Level Compiler

Simple PHP application that compiles textual descripions of Zones (convex 2D polygon) into a binary level asset.

Level compilation involves reading a directory of Zone descriptions, validating certain basic rules about them and discovering how their edges are shared to create a level.

The application is written in PHP for speed of development. The code is stand alone (requires json support) and not remotely PSR compliant.
