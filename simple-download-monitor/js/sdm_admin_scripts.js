document.addEventListener('DOMContentLoaded', function () {

    const exportLogs = document.getElementById('sdm-export-logs-submit');

    exportLogs.addEventListener('click', async function (e) {
        if (!confirm("Are you sure you want to export logs?")) {
            return;
        }

        const search = document.getElementById('sdm-export-logs-search')?.value;
        const order = document.getElementById('sdm-export-logs-order')?.value;
        const orderBy = document.getElementById('sdm-export-logs-orderby')?.value;
        const nonce = document.getElementById('sdm-export-logs-nonce')?.value;

        const payload = new URLSearchParams({
            action: 'sdm_export_logs',
            search,
            order,
            orderBy,
            nonce
        })

        try {
            const response = await fetch(sdm_admin.ajax_url, {
                method: 'post',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: payload,
            })

            const result = await response.json();
            // console.log(result);

            if (!result.success) {
                throw new Error(result.data.message);
            }

            // Download the CSV file.
            downloadCSV(result.data.logs);

        } catch (error) {
            console.log(error);
            alert(error.message);
        }
    });

    function downloadCSV(jsonArray) {
        if (!jsonArray || jsonArray.length === 0) {
            throw new Error('No data available to download.');
        }

        // Convert JSON to CSV

        // Extract headers from the first object
        const headers = Object.keys(jsonArray[0]).join(',');
        const rows = jsonArray.map(row =>
            Object.values(row)
                .map(value => `"${String(value).replace(/"/g, '""')}"`) // Escape quotes
                .join(',')
        );
        const csvContent = [headers, ...rows].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });

        // Create a temporary link element to trigger the download
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.href = url;
        link.download = getCSVFileName();

        // Append link to the DOM
        document.body.appendChild(link);
        // Programmatically trigger the download
        link.click();
        // Clean up
        document.body.removeChild(link); 
        // Free up memory
        URL.revokeObjectURL(url);
    }

    function getCSVFileName(){
        const date = new Date();
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `sdm-download-logs-${year}-${month}-${day}.csv`;
    }

})