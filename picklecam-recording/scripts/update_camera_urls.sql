-- ============================================
-- Update PickleCam Camera RTSP URLs
-- Corrected format: /Streaming/Channels/10X
-- ============================================

UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/101' WHERE court_id = 1;
UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/102' WHERE court_id = 2;
UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/103' WHERE court_id = 3;
UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/104' WHERE court_id = 4;

-- Verify updates
SELECT court_id, name, stream_url FROM cameras WHERE court_id BETWEEN 1 AND 4;
