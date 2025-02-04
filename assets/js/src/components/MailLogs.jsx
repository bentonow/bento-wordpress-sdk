import React from 'react';
import { Button } from '../components/ui/button.jsx';
import { Table, TableHeader, TableBody, TableFooter, TableHead, TableRow, TableCell } from '../components/ui/table.jsx';
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogFooter, AlertDialogTitle, AlertDialogTrigger, AlertDialogDescription, AlertDialogAction, AlertDialogCancel } from '../components/ui/alert-dialog.jsx';
import { useToast } from '../hooks/use-toast';
import { CheckCircle2, AlertCircle, AlertTriangle, RotateCcw } from 'lucide-react';

const MailLogs = ({ logs, nonce, adminUrl }) => {
  const { toast } = useToast();

  const handleClearLogs = async () => {
    try {
      const response = await fetch(adminUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'clear_bento_mail_logs',
          _wpnonce: nonce
        })
      });

      if (response.ok) {
        toast({
          title: "Logs Cleared",
          description: "Mail logs have been cleared successfully.",
          duration: 3000,
        });
        // Reload the page to show cleared logs
        window.location.reload();
      } else {
        throw new Error('Failed to clear logs');
      }
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to clear logs. Please try again.",
        variant: "destructive",
        duration: 3000,
      });
    }
  };

  const getStatusIcon = (type, success) => {
    switch (type) {
      case 'blocked_duplicate':
        return <AlertTriangle className="h-4 w-4 text-yellow-500" />;
      case 'wordpress_fallback':
        return <RotateCcw className="h-4 w-4 text-blue-500" />;
      default:
        return success ?
          <CheckCircle2 className="h-4 w-4 text-green-500" /> :
          <AlertCircle className="h-4 w-4 text-red-500" />;
    }
  };

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Bento Mail Logs</h1>

        <AlertDialog>
          <AlertDialogTrigger asChild>
            <Button variant="destructive">Clear Logs</Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Are you sure?</AlertDialogTitle>
              <AlertDialogDescription>
                This will permanently delete all mail logs. This action cannot be undone.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>Cancel</AlertDialogCancel>
              <AlertDialogAction onClick={handleClearLogs}>Clear Logs</AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>

      <div>
        <Table>
          <TableHeader className={'border-b'}>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Time</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>To</TableHead>
              <TableHead>Subject</TableHead>
              <TableHead>Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody className={'bg-white'}>
            {logs.map((log) => (
              <TableRow key={log.id}>
                <TableCell>{log.id}</TableCell>
                <TableCell>{log.timestamp}</TableCell>
                <TableCell>{log.type}</TableCell>
                <TableCell>{log.to}</TableCell>
                <TableCell>{log.subject}</TableCell>
                <TableCell>
                  <div className="flex items-center gap-2">
                    {getStatusIcon(log.type, log.success)}
                    <span>{log.type === 'blocked_duplicate' ? 'Blocked' :
                      log.type === 'wordpress_fallback' ? 'WordPress' :
                        log.success ? 'Sent' : 'Failed'}</span>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </div>
  );
};

export default MailLogs;