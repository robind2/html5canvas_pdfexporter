# html5canvas_pdfexporter
A Laravel-based HTML5 drag and droppable canvas used to export Google Chart SVG images to PDF format

This project was created to automate the creation of 'insight reports' which are PDF reports issued at the end of an ad campaign. Insight reports contain multiple Google Charts which represent how an ad performed across a particular dimension. Dimensions can be things like gender, geography, weather, age, etc., so that the client that purchased the ad can see how the ad performed under certain contextual conditions. 

Users can choose a dimension (like gender) to add to the canvas, select which type of chart they would like to visualize the data as (ie: bar chart, column chart, donut chart, etc.), and then a new dimension widget will be added to the canvas, which can then be dragged around. This interactive canvas was created using a Javascript library called 'shapeshift'. The widget will resize itself depending on how much data exists in the database for the dimension, and an SVG tag will be generated and used to display in the browser canvas. Then, before exporting to PDF, this SVG tag will be exported to a file so the PDF exporter can reference an <img> tag. (The PDF exporter library I used, called called 'dompdf' doesn't parse <svg> tags). Using the rough CSS of the canvas, dompdf will create the PDF.


- insight-report.js is the main JS file, and contains all JS functionality related to the HTML5 canvas and PDF export. 
- The InsightReportController.php file is the controller for the tool, which has lots of Laravel related stuff
- The rest of the files are Laravel view files related to the actions available on the page
