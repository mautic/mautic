**Simple   Segments Test cases**                                    

| Segment ID | Type                  | Field            | Value                | Contacts in segment                 |
|------------|-----------------------|------------------|----------------------|-------------------------------------|
| 1          | Equal                 | email            | equal@mailinator.com | 1                                   |
| 2          | Equal                 | First Name       | John                 | 1                                   |
| 3          | Not Equal             | First Name       | John                 | All minus 1 : count = 29            |
| 4          | Empty                 | First Name       |                      | 4                                   |
| 5          | Not Empty             | Email            |                      | 1,2,4,5,6,8,9,10,11                 |
| 6          | Like                  | Address Line 1   | avenue               | 2                                   |
| 7          | Not Like              | Address Line 1   | main                 | All minus 3 count = 29              |
| 8          | Starts With           | City             | MA                   | 1                                   |
| 9          | Ends With             | Last Name        | ma                   | 3                                   |
| 10         | Contains              | City             | ma                   | 1,2                                 |
| 11         | Including             | Country          | Albania,Angola       | 19,20                               |
| 12         | Excluding             | Country          | Angola               | all minus 19                        |
| 13         | Greater Than          | Attribution      | 100                  | 21                                  |
| 14         | Greater or Equal Than | Attribution      | 90                   | 21,22,25                            |
| 15         | Less Than             | Attribution      | 50                   | All minus 21,22,25,26 count = 26    |
| 16         | Less Or Equal Than    | Attribution      | 40                   | All minus 21,22,23,25,26 count = 25 |
| 17         | Date Greater Than     | Attribution Date | Tomorrow +3PM        | 27                                  |
| 18         | Date Greater Equal    | Attribution Date | Tomorrow +3PM        | 27,29                               |
| 19         | Date Less Than        | Attribution Date | Tomorrow +3PM        | 28                                  |
| 20         | Date Less Equal       | Attribution Date | Tomorrow +3PM        | 28,29                               |


**Complex Segments Test Cases**

| Segment ID | Type               | Field          | Value    | Contacts in segment |
|----|----------------------------|----------------|----------|---------------------|
| 17 | Equal                      | Last Name      | Wayne    | 5,8                 |
|    | AND Including              | Country        | IR       |                     |
|    | AND Not Empty              | Email          |          |                     |
| 18 | Equal                      | Last Name      | Banner   | 9,10,11             |
|    | OR Equal                   | City           | Miami    |                     |
|    | OR like                    | Address Line 1 | Markets  |                     |
| 19 | Equal                      | City           | Orlando  | 12,13               |
|    | AND Equal                  | Last Name      | Parker   |                     |
|    | OR Equal                   | Last Name      | Magic    |                     |
| 20 | Equal                      | First Name     | Superman | 14,15               |
|    | OR Equal                   | First Name     | Kal      |                     |
|    | AND Equal                  | Last Name      | El       |                     |
| 21 | Equal                      | First Name     | Scott    | 16,18               |
|    | And Equal                  | Last Name      | Summers  |                     |
|    | OR Equal                   | First Name     | Jean     |                     |
|    | And Equal                  | Last Name      | Gray     |                     |



**Segment Data**

| Contact ID | firstname            | lastname | email                      | City           | Country | address1            | attribution | attribution_date |
|------------|----------------------|----------|----------------------------|----------------|---------|---------------------|-------------|------------------|
| 1          | John                 | Sparrow  | equal@mailinator.com       | Massachussetts |         |                     |             |                  |
| 2          | David                | Moore    | dmoore@mailinator.com      | Florima        |         | 3rd Avenue          |             |                  |
| 3          | Remy                 | Dima     |                            |                |         | main street         |             |                  |
| 4          |                      | Sputnik  | sput@mailinator.com        |                |         |                     |             |                  |
| 5          | Bruce                | Wayne    | imbatman@mailinator.com    |                | Ireland |                     |             |                  |
| 6          | Not Batman           | Wayne    | notbatman@mailinator.com   |                | Mexico  |                     |             |                  |
| 7          | YAB                  | Wayne    |                            |                | Miramar |                     |             |                  |
| 8          | Maybe                | Wayne    | maybebatman@mailinator.com |                | Miramar |                     |             |                  |
| 9          | Bruce                | Banner   | smash@mailinator.com       |                |         | Markets diagonal    |             |                  |
| 10         | Mark                 | Thompson | mt@mailinator.com          | Miami          |         |                     |             |                  |
| 11         | Jon                  | Minute   | jm@mailinator.com          |                |         | Markets information |             |                  |
| 12         | Peter                | Parker   |                            | Orlando        |         |                     |             |                  |
| 13         | Ramy                 | Magic    |                            |                |         |                     |             |                  |
| 14         | Superman             |          |                            |                |         |                     |             |                  |
| 15         | Kal                  | El       |                            |                |         |                     |             |                  |
| 16         | Scott                | Summers  |                            |                |         |                     |             |                  |
| 17         | Marcos               | Summers  |                            |                |         |                     |             |                  |
| 18         | Jean                 | Gray     |                            |                |         |                     |             |                  |
| 19         | Angolan              | Citizen  |                            |                | Angola  |                     |             |                  |
| 20         | Albania              | Citizen  |                            |                | Albania |                     |             |                  |
| 21         | Greater than 100     |          |                            |                |         |                     | 101         |                  |
| 22         | Greater or equal 90  |          |                            |                |         |                     | 90          |                  |
| 23         | Less than 50         |          |                            |                |         |                     | 45          |                  |
| 24         | LessEqual40          |          |                            |                |         |                     | 40          |                  |
| 25         | 100 exactly          |          |                            |                |         |                     | 100         |                  |
| 26         | 50 exactly           |          |                            |                |         |                     | 50          |                  |
| 27         | Date Tomorrow 3:01pm |          |                            |                |         |                     |             | Tomorrow 3:01pm  |
| 28         | Date Tomorrow 2:59pm |          |                            |                |         |                     |             | Tomorrow 2:59pm  |
| 29         | Next Day 3Pm         |          |                            |                |         |                     |             | Tomorrow 3pm     |