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
      const response = await fetch(window.bentoAdmin.ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'bento_get_latest_event',
          _wpnonce: window.bentoAdmin.nonce
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
          
          // Add new event and sort by timestamp (newest first), then limit to 5 events
          const allEvents = [newEvent, ...prevEvents];
          const sortedEvents = allEvents.sort((a, b) => b.timestamp - a.timestamp); // Sort newest first
          const updatedEvents = sortedEvents.slice(0, 5); // Take first 5 (newest) events
          
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

  const EventItem = ({ event, isNew }) => {
    const isError = event.is_error;
    
    return (
      <div className={`flex items-center space-x-3 p-3 rounded-lg border transition-all duration-300 hover:bg-gray-50 hover:border-gray-300 motion-safe:hover:scale-[1.01] ${
        isNew ? 'motion-safe:animate-in motion-safe:slide-in-from-top-2 motion-safe:duration-500' : ''
      } ${
        isError 
          ? 'bg-red-50 border-red-200 hover:bg-red-100' 
          : isNew 
            ? 'bg-blue-50 border-blue-200' 
            : 'bg-white'
      }`}>
        <span className="text-lg flex-shrink-0">
          {isError ? '⚠️' : integrationIcons[event.integration]}
        </span>
        <div className="flex-1 min-w-0">
          <div className="flex items-center space-x-2 flex-wrap sm:flex-nowrap">
            <Badge variant={isError ? "destructive" : "secondary"} className="text-xs flex-shrink-0">
              {isError ? 'Error' : event.integration}
            </Badge>
            <span className={`text-sm font-medium truncate ${
              isError ? 'text-red-900' : 'text-gray-900'
            }`}>
              {event.type}
            </span>
          </div>
          <p className={`text-sm truncate mt-1 ${
            isError ? 'text-red-700' : 'text-gray-500'
          }`}>
            {isError ? event.message : event.email}
          </p>
          {isError && event.action_required && (
            <p className="text-xs text-red-600 mt-1 font-medium">
              Action required: {event.action_required}
            </p>
          )}
        </div>
        <div className="flex flex-col items-end text-right flex-shrink-0">
          <span className="text-xs text-gray-400 whitespace-nowrap">
            {formatTimestamp(event.timestamp)}
          </span>
          {isNew && (
            <span className={`text-xs font-medium mt-1 ${
              isError ? 'text-red-600' : 'text-blue-600'
            }`}>
              {isError ? 'Error' : 'New'}
            </span>
          )}
        </div>
      </div>
    );
  };

  const EmptyState = () => (
    <div className="text-center py-8 text-gray-500">
      <div className="text-2xl mb-2">
        <video
          src={`${window.bentoAdmin?.pluginUrl || ''}/assets/img/bento-placeholder.mp4`}
          autoPlay
          loop
          muted
          playsInline
          className="m-auto h-24 w-24 rounded"
        ></video>
      </div>
      <p className="text-sm">No recent events</p>
      <p className="text-xs text-gray-400 mt-1">
        Live events will appear here as they occur
      </p>
    </div>
  );

  return (
    <Card className="mb-6 rounded-md break-inside-avoid-column">
      <CardHeader>
        <CardTitle>
          <div className="flex items-center space-x-2">
            <div className="relative w-3 h-3">
              <div className="absolute inset-0 rounded-full bg-emerald-200 animate-ping" />
              <div className="relative rounded-full bg-emerald-500 w-2 h-2 m-auto top-1/2 transform -translate-y-1/2" />
            </div>
            <span>Live Event Sampling</span>
          </div>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {events.length === 0 ? (
          <EmptyState />
        ) : (
          <div className="space-y-2">
            {/* Events are displayed in order: newest first (top of list) */}
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