# Models – fachliche Datenobjekte

`Event`, `EventTask` und `TaskAssignment` sind unveränderliche, typisierte Datenobjekte. Sie kennen keine HTTP-Superglobals, keinen SQL-Client und keine HTML-Ausgabe. Der Controller erfasst Eingaben, der Service prüft Regeln und erzeugt diese Objekte, und das Repository speichert sie.
