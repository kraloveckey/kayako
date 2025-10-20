-- Create tmp tables
DROP TEMPORARY TABLE IF EXISTS ticketids;
DROP TEMPORARY TABLE IF EXISTS trackids;

CREATE TEMPORARY TABLE ticketids (ticketid INT);
CREATE TEMPORARY TABLE trackids (tickettimetrackid INT);

-- Fill temp tables
INSERT INTO ticketids(ticketid)
SELECT ticketid FROM swtickets WHERE departmentid = 0;

INSERT INTO trackids(tickettimetrackid)
SELECT tickettimetrackid FROM swtickettimetracks A LEFT JOIN ticketids B ON A.ticketid = B.ticketid;
    
-- Cleanup all ticket-related tables
DELETE A FROM swtickets A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketpostlocks A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketlocks A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketlinkchains A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketfollowups A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketdrafts A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swattachments A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swescalationpaths A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketnotes A JOIN ticketids B ON A.linktypeid = B.ticketid;
DELETE A FROM swticketauditlogs A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketlinkedtables A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketmergelog A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketmessageids A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketposts A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketrecipients A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swticketwatchers A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swcustomfieldvalues A JOIN ticketids B ON A.typeid = B.ticketid;
DELETE A FROM swtickettimetracks A JOIN ticketids B ON A.ticketid = B.ticketid;
DELETE A FROM swtickettimetracknotes A JOIN trackids B ON A.tickettimetrackid = B.tickettimetrackid;

-- Drop tmp tables
DROP TEMPORARY TABLE IF EXISTS ticketids;
DROP TEMPORARY TABLE IF EXISTS trackids;
