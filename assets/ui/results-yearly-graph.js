$(document).ready(() => {
    function initializeSelectMultiple(selector, multiple = true) {
        const select = $(selector);
        const options = select.find('option');
        const placeholder = select.closest('.selectMultiple').data('placeholder');

        const div = $('<div />').addClass('selectMultiple');
        const active = $('<div />');
        const list = $('<ul />');
        const span = $('<span />').text(placeholder).appendTo(active);

        options.each(function () {
            const text = $(this).text();
            if ($(this).is(':selected')) {
                active.append($('<a />').html(`<em>${text}</em><i></i>`));
                span.addClass('hide');
            } else {
                list.append($('<li />').text(text));
            }
        });

        $(document).on('click', function (e) {
            const target = $(e.target);
            if (!target.closest('.selectMultiple').length) {
                $('.selectMultiple').removeClass('open');
            }
        });

        active.append($('<div />').addClass('arrow'));
        div.append(active).append(list);

        select.wrap(div);

        $(document).on('click', '.selectMultiple ul li', function () {
            const select = $(this).closest('.selectMultiple');
            const li = $(this);
            if (!select.hasClass('clicked')) {
                select.addClass('clicked');
                li.prev().addClass('beforeRemove');
                li.next().addClass('afterRemove');
                li.addClass('remove');

                // For single selection, check if there is already a selected item
                if (!multiple && select.find('a').length > 0) {
                    // Remove the existing selected item
                    select.find('a').each(function () {
                        const a = $(this);
                        const text = a.find('em').text();
                        select.find('option').filter(function () {
                            return $(this).text() === text;
                        }).prop('selected', false);
                        a.remove();
                    });
                }

                const a = $('<a />').addClass('notShown').html(`<em>${li.text()}</em><i></i>`).hide().appendTo(select.children('div'));
                a.slideDown(400, function () {
                    setTimeout(function () {
                        a.addClass('shown');
                        select.children('div').children('span').addClass('hide');
                        select.find('option').filter(function () {
                            return $(this).text() === li.text();
                        }).prop('selected', true);
                    }, 500);
                });

                setTimeout(function () {
                    li.prev().removeClass('beforeRemove');
                    li.next().removeClass('afterRemove');
                    li.slideUp(400, function () {
                        li.remove();
                        select.removeClass('clicked');
                    });
                }, 600);
            }
        });

        $(document).on('click', '.selectMultiple > div a', function () {
            const select = $(this).closest('.selectMultiple');
            const self = $(this);
            self.removeClass().addClass('remove');
            select.addClass('open');
            setTimeout(function () {
                self.addClass('disappear');
                setTimeout(function () {
                    self.animate({
                        width: 0,
                        height: 0,
                        padding: 0,
                        margin: 0
                    }, 300, function () {
                        const li = $('<li />').text(self.children('em').text()).addClass('notShown').appendTo(select.find('ul'));
                        li.slideDown(400, function () {
                            li.addClass('show');
                            setTimeout(function () {
                                select.find('option').filter(function () {
                                    return $(this).text() === self.children('em').text();
                                }).prop('selected', false);
                                if (!select.find('option:selected').length) {
                                    select.children('div').children('span').removeClass('hide');
                                }
                                li.removeClass();
                            }, 400);
                        });
                        self.remove();
                    })
                }, 300);
            }, 400);
        });

        $(document).on('click', '.selectMultiple > div .arrow, .selectMultiple > div span', function () {
            $(this).closest('.selectMultiple').toggleClass('open');
        });
    }

    // Initialize species selector
    initializeSelectMultiple('#species-select', true);

    // Initialize event and year selectors
    initializeSelectMultiple('#event-select', false);
    initializeSelectMultiple('#year-select', false);

    if (observationsData.length === 0) {
        document.querySelector(".no-data").style.display = "block";
    } else {
        // Function to compute density and assign opacity
        function computeDensity(data) {
            const density = {};

            // Compute the density for each event and date
            data.forEach(d => {
                const key = `${d.espece} - ${d.event}`;
                const date = d.date.toDateString();
                if (!density[key]) {
                    density[key] = {};
                }
                if (!density[key][date]) {
                    density[key][date] = 0;
                }
                density[key][date]++;
            });

            // Find the maximum density for each event
            const maxDensity = {};
            for (const key in density) {
                maxDensity[key] = Math.max(...Object.values(density[key]));
            }

            // Calculate opacity based on density
            const opacityData = data.map(d => {
                const key = `${d.espece} - ${d.event}`;
                const date = d.date.toDateString();
                const count = density[key][date];
                const maxCount = maxDensity[key];

                // Ensure a minimum opacity if maxCount is less than 20
                const minOpacity = 0.50; // Set your desired minimum opacity here
                let opacity = (count / maxCount) * 100; // Scale opacity from 0 to 100
                if (maxCount < 20) {
                    opacity = Math.max(opacity, minOpacity * 100); // Scale minOpacity to percentage
                }

                return {
                    ...d,
                    key: key,
                    opacity: opacity,
                    count: count // Include count for tooltip
                };
            });

            return opacityData;
        }

        // Define colorByEvent function
        function colorByEvent(event) {
            switch (event.toLowerCase().trim()) {  // Ensure case-insensitivity
                case "feuillaison 11":
                case "feuillaison 15":
                    return "#BCD35F";
                case "floraison 61":
                case "floraison 65":
                    return "#5FBCD3";
                case "fructification 85":
                    return "#F9872F";
                case "sénescence 91":
                case "sénescence 95":
                    return "#C83737";
                case "1ère apparition":
                    return "#C885B8";
                default:
                    return "black";
            }
        }

        // Combined Year Chart function
        function combinedYearChart(data) {
            // Parse the date / time
            const parseDate = d3.timeParse("%d-%m-%y");

            // Convert dates and prepare data
            data.forEach(function (d) {
                d.date = parseDate(d.date);
            });

            // Compute density and assign opacity
            const densityData = computeDensity(data);

            // Set the dimensions and margins of the graph
            const margin = {top: 70, right: 30, bottom: 50, left: 200}, // Adjusted left margin
                heightPerEvent = 10,
                width = 600;

            // Determine the range of dates and unique event-species combinations
            const eventSpecies = Array.from(new Set(densityData.map(d => d.key)));

            // Calculate the height based on the number of unique event-species combinations
            const height = 400 + eventSpecies.length * heightPerEvent - margin.top - margin.bottom;

            // Append the svg object to the body of the page
            const svg = d3.select("#singleChart")
                .append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + (margin.top + 30) + ")");

            // Set the ranges for a single year span (from January 1 to December 31)
            const year = 2024; // Using a leap year to cover 366 days
            const x = d3.scaleTime().domain([new Date(year, 0, 1), new Date(year, 11, 31)]).range([0, width]);
            const y = d3.scaleBand().domain(eventSpecies).range([0, height]).padding(0.1);

            // Add vertical grid lines
            svg.selectAll("line.vertical-grid")
                .data(x.ticks(d3.timeMonth))
                .enter()
                .append("line")
                .attr("class", "vertical-grid")
                .attr("x1", function (d) {
                    return x(d);
                })
                .attr("x2", function (d) {
                    return x(d);
                })
                .attr("y1", 0)
                .attr("y2", height)
                .attr("stroke", "#000")
                .attr("stroke-dasharray", "2,2");

            // Add the X Axis
            svg.append("g")
                .attr("transform", "translate(0," + height + ")")
                .call(d3.axisBottom(x).ticks(d3.timeMonth).tickFormat(d3.timeFormat("%b")))
                .selectAll("text")
                .style("font-size", "14px"); // Adjust the font size

            // Add the Y Axis
            svg.append("g")
                .call(d3.axisLeft(y))
                .selectAll("text")
                .attr("transform", "translate(-10,0)") // Move text slightly to the left
                .style("text-anchor", "end")// Align text to the end of the axis
                .style("font-size", "12px"); // Adjust the font size

            // Add tooltip div
            const tooltip = d3.select("body").append("div")
                .attr("class", "tooltip")
                .style("opacity", 0);

            // Add squares
            const squareSize = 10; // Define the size of the squares

            svg.selectAll("rect")
                .data(densityData)
                .enter().append("rect")
                .attr("x", function (d) {
                    // Map the dates to the selected year for x-axis positioning
                    const mappedDate = new Date(year, d.date.getMonth(), d.date.getDate());
                    return x(mappedDate) - squareSize / 2; // Center the square
                })
                .attr("y", function (d) {
                    return y(d.key) + y.bandwidth() / 2 - squareSize / 2;
                }) // Center the square
                .attr("width", 3)
                .attr("height", 17)
                .style("fill", function (d) {
                    return colorByEvent(d.event);
                }) // Use the colorByEvent function
                .style("opacity", function (d) {
                    return d.opacity / 100;
                }) // Use computed opacity
                .style("stroke-width", 0) // Initially no border
                .on("mouseover", function (event, d) {
                    d3.select(this)
                        .style("stroke", "black")
                        .style("stroke-width", 2);
                    tooltip.transition()
                        .duration(200)
                        .style("opacity", .9);
                    tooltip.html(d.espece + "<br/>" + d.event + "<br/>" + d3.timeFormat("%d/%m")(d.date) + "<br/>" + d.count + " donnée(s)")
                        .style("left", (event.pageX + 5) + "px")
                        .style("top", (event.pageY - 28) + "px");
                })
                .on("mouseout", function (d) {
                    d3.select(this)
                        .style("stroke-width", 0); // Remove border on mouse out
                    tooltip.transition()
                        .duration(500)
                        .style("opacity", 0);
                });

            // Add the title above the graph
            svg.append("text")
                .attr("x", width / 2)
                .attr("y", -50) // Position the title above the graph
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .style("font-weight", "bold")
                .text("Observations sur l'ensemble des années");
        }

        function combinedSelectedYearChart(data) {
            // Parse the date / time
            const parseDate = d3.timeParse("%d-%m-%y");

            // Convert dates and prepare data
            data.forEach(function (d) {
                d.date = parseDate(d.date);
            });

            // Compute density and assign opacity
            const densityData = computeDensity(data);

            // Set the dimensions and margins of the graph
            const margin = {top: 70, right: 30, bottom: 50, left: 200}, // Adjusted left margin
                heightPerEvent = 10,
                width = 600;

            // Determine the range of dates and unique event-species combinations
            const eventSpecies = Array.from(new Set(densityData.map(d => d.key)));

            // Calculate the height based on the number of unique event-species combinations
            const height = 400 + eventSpecies.length * heightPerEvent - margin.top - margin.bottom;

            // Append the svg object to the body of the page
            const svg = d3.select("#combinedSelectedYearChart")
                .append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + (margin.top + 30) + ")");

            // Set the ranges for a single year span (from January 1 to December 31)
            const year = 2024; // Using a leap year to cover 366 days
            const x = d3.scaleTime().domain([new Date(year, 0, 1), new Date(year, 11, 31)]).range([0, width]);
            const y = d3.scaleBand().domain(eventSpecies).range([0, height]).padding(0.1);

            // Add vertical grid lines
            svg.selectAll("line.vertical-grid")
                .data(x.ticks(d3.timeMonth))
                .enter()
                .append("line")
                .attr("class", "vertical-grid")
                .attr("x1", function (d) {
                    return x(d);
                })
                .attr("x2", function (d) {
                    return x(d);
                })
                .attr("y1", 0)
                .attr("y2", height)
                .attr("stroke", "#000")
                .attr("stroke-dasharray", "2,2");

            // Add the X Axis
            svg.append("g")
                .attr("transform", "translate(0," + height + ")")
                .call(d3.axisBottom(x).ticks(d3.timeMonth).tickFormat(d3.timeFormat("%b")))
                .selectAll("text")
                .style("font-size", "14px"); // Adjust the font size

            // Add the Y Axis
            svg.append("g")
                .call(d3.axisLeft(y))
                .selectAll("text")
                .attr("transform", "translate(-10,0)") // Move text slightly to the left
                .style("text-anchor", "end")// Align text to the end of the axis
                .style("font-size", "12px"); // Adjust the font size

            // Add tooltip div
            const tooltip = d3.select("body").append("div")
                .attr("class", "tooltip")
                .style("opacity", 0);

            // Add squares
            const squareSize = 10; // Define the size of the squares

            svg.selectAll("rect")
                .data(densityData)
                .enter().append("rect")
                .attr("x", function (d) {
                    // Map the dates to the selected year for x-axis positioning
                    const mappedDate = new Date(year, d.date.getMonth(), d.date.getDate());
                    return x(mappedDate) - squareSize / 2; // Center the square
                })
                .attr("y", function (d) {
                    return y(d.key) + y.bandwidth() / 2 - squareSize / 2;
                }) // Center the square
                .attr("width", 3)
                .attr("height", 17)
                .style("fill", function (d) {
                    return colorByEvent(d.event);
                }) // Use the colorByEvent function
                .style("opacity", function (d) {
                    return d.opacity / 100;
                }) // Use computed opacity
                .style("stroke-width", 0) // Initially no border
                .on("mouseover", function (event, d) {
                    d3.select(this)
                        .style("stroke", "black")
                        .style("stroke-width", 2);
                    tooltip.transition()
                        .duration(200)
                        .style("opacity", .9);
                    tooltip.html(d.espece + "<br/>" + d.event + "<br/>" + d3.timeFormat("%d/%m")(d.date) + "<br/>" + d.count + " donnée(s)")
                        .style("left", (event.pageX + 5) + "px")
                        .style("top", (event.pageY - 28) + "px");
                })
                .on("mouseout", function (d) {
                    d3.select(this)
                        .style("stroke-width", 0); // Remove border on mouse out
                    tooltip.transition()
                        .duration(500)
                        .style("opacity", 0);
                });

            // Add the title above the graph
            svg.append("text")
                .attr("x", width / 2)
                .attr("y", -50) // Position the title above the graph
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .style("font-weight", "bold")
                .text("Observations sur l'ensemble des années sélectionnées");
        }

        function eachYearChart(data) {

            data = data.filter(d => d.date && !isNaN(new Date(d.date)));
            // Extract years from the data
            const years = Array.from(new Set(data.map(d => new Date(d.date).getFullYear())));

            // Sort years in descending order
            years.sort((a, b) => b - a);  // Sort in descending order (most recent year first)

            // Iterate over each year to generate charts
            years.forEach(year => {
                // Filter data for the current year
                const yearData = data.filter(d => new Date(d.date).getFullYear() === year);

                // Generate chart for the current year
                generateGraphForYear(year, yearData);
            });
        }

        // Function to generate a graph for a specific year
        function generateGraphForYear(year, data) {

            // Parse the date / time
            const parseDate = d3.timeParse("%d-%m-%y");

            // Convert dates and prepare data
            data.forEach(function (d) {
                d.date = parseDate(d.date);
            });

            // Compute density and assign opacity
            const densityData = computeDensity(data);

            // Set the dimensions and margins of the graph
            const margin = {top: 70, right: 30, bottom: 50, left: 200}, // Adjusted left margin
                heightPerEvent = 10,
                width = 500;

            // Determine the range of dates and unique event-species combinations
            const eventSpecies = Array.from(new Set(densityData.map(d => d.key)));

            // Calculate the height based on the number of unique event-species combinations
            const height = 400 + eventSpecies.length * heightPerEvent - margin.top - margin.bottom;

            // Append the svg object to the body of the page
            const svg = d3.select("#multipleChart")
                .append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom + 30) // Additional height for title
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + (margin.top + 30) + ")"); // Adjusted transformation to fit title

            // Add the title on top of the graph
            svg.append("text")
                .attr("x", width / 2)
                .attr("y", -30) // Position the title above the graph
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .style("font-weight", "bold")
                .text(`Observations pour l'année ${year}`);

            // Set the ranges for a single year span (from January 1 to December 31)
            const x = d3.scaleTime().domain([new Date(year, 0, 1), new Date(year, 11, 31)]).range([0, width]);
            const y = d3.scaleBand().domain(eventSpecies).range([0, height]).padding(0.1);

            // Add vertical grid lines
            svg.selectAll("line.vertical-grid")
                .data(x.ticks(d3.timeMonth))
                .enter()
                .append("line")
                .attr("class", "vertical-grid")
                .attr("x1", function (d) {
                    return x(d);
                })
                .attr("x2", function (d) {
                    return x(d);
                })
                .attr("y1", 0)
                .attr("y2", height)
                .attr("stroke", "#ccc")
                .attr("stroke-dasharray", "2,2");

            // Add the X Axis
            svg.append("g")
                .attr("transform", "translate(0," + height + ")")
                .call(d3.axisBottom(x).ticks(d3.timeMonth).tickFormat(d3.timeFormat("%b")))
                .selectAll("text")
                .style("font-size", "14px"); // Adjust the font size for X axis labels

            // Add the Y Axis
            svg.append("g")
                .call(d3.axisLeft(y))
                .selectAll("text")
                .attr("transform", "translate(-10,0)") // Move text slightly to the left
                .style("text-anchor", "end") // Align text to the end of the axis
                .style("font-size", "14px"); // Adjust the font size for Y axis labels

            // Add tooltip div
            const tooltip = d3.select("body").append("div")
                .attr("class", "tooltip")
                .style("opacity", 0);

            // Add squares
            const squareSize = 10; // Define the size of the squares

            svg.selectAll("rect")
                .data(densityData)
                .enter().append("rect")
                .attr("x", function (d) {
                    // Map the dates to the selected year for x-axis positioning
                    const mappedDate = new Date(year, d.date.getMonth(), d.date.getDate());
                    return x(mappedDate) - squareSize / 2; // Center the square
                })
                .attr("y", function (d) {
                    return y(d.key) + y.bandwidth() / 2 - squareSize / 2;
                }) // Center the square
                .attr("width", 5)
                .attr("height", 17)
                .style("fill", function (d) {
                    return colorByEvent(d.event);
                }) // Use the colorByEvent function
                .style("opacity", function (d) {
                    return d.opacity / 100;
                }) // Use computed opacity
                .style("stroke-width", 0) // Initially no border
                .on("mouseover", function (event, d) {
                    d3.select(this)
                        .style("stroke", "black")
                        .style("stroke-width", 2);
                    tooltip.transition()
                        .duration(200)
                        .style("opacity", .9);
                    tooltip.html("Species: " + d.espece + "<br/>Event: " + d.event + "<br/>Date: " + d3.timeFormat("%d/%m")(d.date) + "<br/>Count: " + d.count)
                        .style("left", (event.pageX + 5) + "px")
                        .style("top", (event.pageY - 28) + "px");
                })
                .on("mouseout", function (d) {
                    d3.select(this)
                        .style("stroke-width", 0); // Remove border on mouse out
                    tooltip.transition()
                        .duration(500)
                        .style("opacity", 0);
                });
        }

        // Call the function to create the charts
        combinedYearChart(observationsData);

        function checkMultipleYears() {
            if (selectedYearData.length > 1) {
                eachYearChart( selectedYearData);
            }
        }

        checkMultipleYears();
    }
});

