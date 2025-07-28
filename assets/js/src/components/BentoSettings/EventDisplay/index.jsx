import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

export function EventDisplay() {
  const [events, setEvents] = useState([]);
  const [newEventIds, setNewEventIds] = useState(new Set());

  const integrationIcons = {
    'WooCommerce': '🛒',
    'LearnDash': '🎓', 
    'SureCart': '💳',
    'EDD': '📦',
    'Forms': '📧',
    'Unknown': '❓'
  };

  const formatTimestamp = (timestamp) => {
    const now = Date.now() / 1000; // Convert to seconds
    const diff = now - timestamp;
    
    if (diff < 60) return `${Math.floor(diff)}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
  };

  const fetchLatestEvent = async () => {
    try {
      const response = await fetch(window.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'bento_get_latest_event',
          _wpnonce: window.bento_nonce
        })
      });

      const result = await response.json();
      
      if (result.success && result.data.event) {
        const newEvent = result.data.event;
        
        setEvents(prevEvents => {
          // Check if event already exists
          const eventExists = prevEvents.some(event => event.id === newEvent.id);
          if (eventExists) {
            return prevEvents;
          }
          
          // Mark as new event
          setNewEventIds(prev => new Set([...prev, newEvent.id]));
          
          // Remove "new" status after 5 seconds
          setTimeout(() => {
            setNewEventIds(prev => {
              const newSet = new Set(prev);
              newSet.delete(newEvent.id);
              return newSet;
            });
          }, 5000);
          
          // Add new event to front and limit to 5 events
          const updatedEvents = [newEvent, ...prevEvents].slice(0, 5);
          return updatedEvents;
        });
      }
    } catch (error) {
      console.error('Failed to fetch latest event:', error);
    }
  };

  useEffect(() => {
    // Initial fetch
    fetchLatestEvent();
    
    // Poll every 3 seconds
    const interval = setInterval(fetchLatestEvent, 3000);
    
    // Update timestamps every 30 seconds for accuracy
    const timestampInterval = setInterval(() => {
      setEvents(prevEvents => [...prevEvents]); // Force re-render for timestamp updates
    }, 30000);
    
    return () => {
      clearInterval(interval);
      clearInterval(timestampInterval);
    };
  }, []);

  const EventItem = ({ event, isNew }) => (
    <div className={`flex items-center space-x-3 p-3 rounded-lg border transition-all duration-300 hover:bg-gray-50 hover:border-gray-300 motion-safe:hover:scale-[1.01] ${
      isNew ? 'motion-safe:animate-in motion-safe:slide-in-from-top-2 motion-safe:duration-500 bg-blue-50 border-blue-200' : 'bg-white'
    }`}>
      <span className="text-lg flex-shrink-0">{integrationIcons[event.integration]}</span>
      <div className="flex-1 min-w-0">
        <div className="flex items-center space-x-2 flex-wrap sm:flex-nowrap">
          <Badge variant="secondary" className="text-xs flex-shrink-0">
            {event.integration}
          </Badge>
          <span className="text-sm font-medium text-gray-900 truncate">
            {event.type}
          </span>
        </div>
        <p className="text-sm text-gray-500 truncate mt-1">{event.email}</p>
      </div>
      <div className="flex flex-col items-end text-right flex-shrink-0">
        <span className="text-xs text-gray-400 whitespace-nowrap">
          {formatTimestamp(event.timestamp)}
        </span>
        {isNew && (
          <span className="text-xs text-blue-600 font-medium mt-1">New</span>
        )}
      </div>
    </div>
  );

  const EmptyState = () => (
    <div className="text-center py-8 text-gray-500">
      <div className="text-2xl mb-2">📡</div>
      <p className="text-sm">No recent events</p>
      <p className="text-xs text-gray-400 mt-1">
        Live events will appear here as they occur
      </p>
    </div>
  );

  return (
    <Card className="mb-6 rounded-md break-inside-avoid-column">
      <CardHeader>
        <CardTitle>Live Events</CardTitle>
      </CardHeader>
      <CardContent>
        {events.length === 0 ? (
          <EmptyState />
        ) : (
          <div className="space-y-2">
            {events.map((event) => (
              <EventItem 
                key={event.id} 
                event={event} 
                isNew={newEventIds.has(event.id)} 
              />
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}