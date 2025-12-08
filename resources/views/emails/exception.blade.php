<!DOCTYPE html>
<html>
<head>
    <title>Exception Report</title>
</head>
<body>
<h1>Exception Report</h1>
<p><strong>Exception:</strong> {{ get_class($exception) }}</p>
<p><strong>Message:</strong> {{ $exception->getMessage() }}</p>
<p><strong>File:</strong> {{ $exception->getFile() }}</p>
<p><strong>Line:</strong> {{ $exception->getLine() }}</p>
<p><strong>Trace:</strong></p>
<pre>{{ $exception->getTraceAsString() }}</pre>
</body>
</html>
