# Services – Fachlogik

`EventService` validiert Veranstaltungen; `TaskService` validiert freie Aufgaben mit Ort, Start, Ende und ausgewählten Mitgliedern. Eine Aufgabe kann beliebig viele Mitglieder enthalten (aus Sicherheits-/Bedienbarkeitsgründen höchstens 500 pro Formular). Die Services kennen weder `$_POST` noch HTML oder SQL. Die Speicherung erfolgt ausschließlich über `EventRepository`.
