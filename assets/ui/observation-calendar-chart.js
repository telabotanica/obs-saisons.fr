import * as d3 from 'd3';
$(function () {
	if (document.getElementById("allDataChart")) {
		let url = `${calendarRoute}?`;
		(async() => {
			getDataforCalendar();
		})();
		
	}
    if (checkCalendarDivs()) {
        // Function to initialize the select multiple plugin
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
                        
                        setTimeout(async function () {
                            a.addClass('shown');
                            select.children('div').children('span').addClass('hide');
                            select.find('option').filter(function () {
                                return $(this).text() === li.text();
                            }).prop('selected', true);
                            await getDataforCalendar();
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
                    setTimeout(async function () {
                        self.animate({
                            width: 0,
                            height: 0,
                            padding: 0,
                            margin: 0
                        }, 300, function () {
                            const li = $('<li />').text(self.children('em').text()).addClass('notShown').appendTo(select.find('ul'));
                            
                            li.slideDown(400, function () {
                                li.addClass('show');
                                setTimeout(async function () {
                                    select.find('option').filter(function () {
                                        return $(this).text() === self.children('em').text();
                                    }).prop('selected', false);
                                    if (!select.find('option:selected').length) {
                                        select.children('div').children('span').removeClass('hide');
                                    }
                                    li.removeClass();
                                    await getDataforCalendar();
                                    initOnChange();
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
        
        initializeSelectMultiple('#species-calendar-chart', true);
        initializeSelectMultiple('#event-calendar-chart', false);
        initializeSelectMultiple('#year-calendar-chart', false);
        initOnChange();
    }
});
async function getDataforCalendar(){
    const criteria = {
        species: $('#species-calendar-chart').val(),
        event: $('#event-calendar-chart').val(),
        year: $('#year-calendar-chart').val()
    };
    if(criteria.event.includes('0')){
        criteria.event=[];
    }
    if(criteria.year.includes('1')){
        criteria.year=[];
    }
    let data = await retrieveData(generatedUrl(criteria));
   
    if(data.length > 0){
        $('#alerteCalendar').hide();
        $('#combinedYearChart').show();
        $('#combinedSelectedYearChart').show();
    }else{
        $('#alerteCalendar').show();
        $('#combinedYearChart').hide();
        $('#combinedSelectedYearChart').hide();
    }
    criteria.year = "";
    handleData(data, criteria);
    combinedYearChart(handleData(data, criteria));
    
    criteria.year = $('#year-calendar-chart').val();
    
    let observationByYearSelected = handleData(data, criteria);
    
    displayCalendars(observationByYearSelected, criteria);
}
function generatedUrl(criteria) {
    // base route generated from the template
    
    let url = `${calendarRoute}?`;
	
    // string used in url for array in params
    let temp = "%5B%5D"
    for (let i = 0; i < criteria.species.length; i++) {
        url += 'species' + temp + '=' + criteria.species[i] + '&';
    }
    if (criteria.event.length === 0 && criteria.year.length === 0) {
        url = url.slice(0, -1);
        return url;
    }
    //if event -> add to url
    for (let i = 0; i < criteria.event.length; i++) {
        url += 'event' + temp + '=' + criteria.event[i] + '&';
    }
    for (let i = 0; i < criteria.year.length; i++) {
        url += 'year' + temp + '=' + criteria.year[i] + '&';
    }
    return url;
}
async function retrieveData(url) {
	let data
  
    await $.ajax({
        method: "GET",
        url: url,
        dataType: "json",
        success: d => {
			data = d
        }
    })
    return data
}
 function handleData(data, criteria){
    const selectedYears = criteria.year
    const observationByYearSelected = [];
    for (let observation of data) {
		 
        const observationYear = new Date(observation.date).getFullYear().toString();
        if (selectedYears.includes(observationYear) || selectedYears == "") {
             observationByYearSelected.push({
                 date: new Date(observation.date).toLocaleDateString('en-GB'), // Format date to 'dd-mm-yy'
                 event: `${observation.event.displayName}`,
                 eventObsName: observation.event.name,
                 espece: observation.individual.species.displayName
             });
         }
     }
     return observationByYearSelected;
 }
// Function to compute density and assign opacity
function computeDensity(data) {
    const density = {};
    // Compute the density for each event and date
    let i = 0
    data.forEach(d => {
        const key = `${d.espece} - ${d.event}`;
        const date = d.date.toDateString();
        if (!density[key]) {
            density[key] = {};
        }
        if (!density[key][date]) {
            density[key][date]++;
        }
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
function combinedYearChart(data, graphId = "#combinedYearChart") {
    // Parse the date / time
    const parseDate = d3.timeParse("%d/%m/%Y");
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
    d3.select(graphId).html("");
    const svg = d3.select(graphId)
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
	let i = 1
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
// Function to generate a graph for a specific year
function generateGraphForYear(years, data) {

    // Parse the date / time
    const parseDate = d3.timeParse("%d/%m/%Y");
	let year = years[0];
    // Convert dates and prepare data
    data.forEach(function (d) {
        d.date = parseDate(d.date);
    });
    
    let label = "Observations pour ";
    if (years.length > 1) {
		label += "les années ";
	}else if (years.length==1){
		label += "l'année ";
	}else if(years.includes('1')){
        label="toutes les années";
    }
    console.log(label);
    years.forEach(function(y) {
		label += y + " ";
	})

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
    d3.select("#combinedSelectedYearChart").html("");
    const svg = d3.select("#combinedSelectedYearChart")
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
        .text(label);
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
// Function to check for multiple years and call the appropriate chart functions
function checkMultipleYears(dataBySelectedYear, criteria) {
    // Check if there are multiple selected years
   
    if (criteria.year.length > 0) {
		generateGraphForYear(criteria.year, dataBySelectedYear);
    }
}
function displayCalendars(dataBySelectedYear, criteria) {
    checkMultipleYears(dataBySelectedYear, criteria);
    
}
function checkCalendarDivs() {
    // Check if the calendar divs are present
    
    return !!(document.getElementById("combinedYearChart")
        && document.getElementById("combinedSelectedYearChart"));
}
function initOnChange() {
    $('#species-calendar-chart').on('change');
    $('#event-calendar-chart').on('change');
    $('#year-calendar-chart').on('change');
}