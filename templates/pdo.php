<?php

// Cleanup outdated database entries
// Clear dates
$currentDate = (new dateTime('now'))->format('Y-m-d');
setDatabaseRequest("DELETE FROM dates WHERE dateTime < '$currentDate'");

// Clear appointments where dateID does not exist
setDatabaseRequest('DELETE FROM appointments a WHERE NOT EXISTS ( SELECT 1 FROM dates d WHERE d.dateID = a.dateID )');

// Clear client where ID do not exist in appointments
setDatabaseRequest('DELETE FROM clients c WHERE NOT EXISTS ( SELECT 1 FROM appointments a WHERE c.clientID = a.clientID )');